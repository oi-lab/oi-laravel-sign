<?php

namespace OiLab\OiLaravelSign\Models;

use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use OiLab\OiLaravelSign\Actions\VerifySignedDocument;
use OiLab\OiLaravelSign\Contracts\Signable;
use OiLab\OiLaravelSign\Data\BrandingData;
use OiLab\OiLaravelSign\Data\SignatureAttachmentData;
use OiLab\OiLaravelSign\Data\SignatureEventData;
use OiLab\OiLaravelSign\Data\SignatureEvidenceData;
use OiLab\OiLaravelSign\Data\SignatureRequestData;
use OiLab\OiLaravelSign\Data\SignerData;
use OiLab\OiLaravelSign\Database\Factories\SignatureRequestFactory;
use OiLab\OiLaravelSign\Enums\OtpChannelType;
use OiLab\OiLaravelSign\Enums\SignatureEventType;
use OiLab\OiLaravelSign\Enums\SignatureStatus;
use OiLab\OiLaravelSign\Observers\SignatureRequestObserver;
use OiLab\OiLaravelSign\OiLaravelSign;

/**
 * A request for one signer to sign one document. The document, its
 * attachments and the signed PDF are stored under {directory}/{uuid}/; the
 * link token is only kept as a SHA-256 hash.
 *
 * @property int $id
 * @property string $uuid
 * @property string $signable_type
 * @property int|string $signable_id
 * @property string|null $owner_type
 * @property int|string|null $owner_id
 * @property string $signer_name
 * @property string $signer_email
 * @property string|null $signer_phone
 * @property string|null $signer_role
 * @property string $token_hash
 * @property SignatureStatus $status
 * @property string $document_path
 * @property string $document_hash
 * @property string|null $document_version
 * @property OtpChannelType $otp_channel
 * @property Carbon|null $expires_at
 * @property Carbon|null $viewed_at
 * @property Carbon|null $signed_at
 * @property string|null $signed_pdf_path
 * @property string|null $decline_reason
 * @property int|null $created_by
 * @property int|null $updated_by
 * @property array<string, mixed>|null $props
 * @property Carbon|null $deleted_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read (Model&Signable)|null $signable
 * @property-read Model|null $owner
 */
#[ObservedBy(SignatureRequestObserver::class)]
class SignatureRequest extends Model
{
    /** @use HasFactory<SignatureRequestFactory> */
    use HasFactory;

    use HasUuids;
    use SoftDeletes;

    protected $table = 'signature_requests';

    /** @var list<string> */
    protected $fillable = [
        'uuid',
        'signable_type',
        'signable_id',
        'owner_type',
        'owner_id',
        'signer_name',
        'signer_email',
        'signer_phone',
        'signer_role',
        'token_hash',
        'status',
        'document_path',
        'document_hash',
        'document_version',
        'otp_channel',
        'expires_at',
        'viewed_at',
        'signed_at',
        'signed_pdf_path',
        'decline_reason',
        'created_by',
        'updated_by',
        'props',
    ];

    /** @var list<string> */
    protected $hidden = ['token_hash'];

    /** @var array<string, mixed> */
    protected $attributes = [
        'status' => 'pending',
        'otp_channel' => 'mail',
    ];

    public function uniqueIds(): array
    {
        return ['uuid'];
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => SignatureStatus::class,
            'otp_channel' => OtpChannelType::class,
            'expires_at' => 'datetime',
            'viewed_at' => 'datetime',
            'signed_at' => 'datetime',
            'created_by' => 'integer',
            'updated_by' => 'integer',
            'props' => 'array',
        ];
    }

    public static function hashToken(string $token): string
    {
        return hash('sha256', $token);
    }

    public static function findByToken(string $token): ?static
    {
        return static::query()->where('token_hash', static::hashToken($token))->first();
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function signable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function owner(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @return HasMany<SignatureEvent, $this>
     */
    public function events(): HasMany
    {
        return $this->hasMany(OiLaravelSign::signatureEventModel())->orderBy('id');
    }

    /**
     * @return HasMany<SignatureOtp, $this>
     */
    public function otps(): HasMany
    {
        return $this->hasMany(OiLaravelSign::signatureOtpModel());
    }

    /**
     * @return BelongsTo<Model, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(OiLaravelSign::userModel(), 'created_by');
    }

    /**
     * @return BelongsTo<Model, $this>
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(OiLaravelSign::userModel(), 'updated_by');
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereIn('status', SignatureStatus::open());
    }

    public function isOpen(): bool
    {
        return $this->status->isOpen();
    }

    public function isSigned(): bool
    {
        return $this->status === SignatureStatus::Signed;
    }

    public function isPastExpiry(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function isLocked(): bool
    {
        $until = $this->prop('locked_until');

        return $until !== null && Carbon::parse($until)->isFuture();
    }

    public function lockedUntil(): ?Carbon
    {
        $until = $this->prop('locked_until');

        return $until === null ? null : Carbon::parse($until);
    }

    public function prop(string $key, mixed $default = null): mixed
    {
        return Arr::get($this->props ?? [], $key, $default);
    }

    /**
     * Set props by dot key, without saving.
     *
     * @param  array<string, mixed>  $values
     */
    public function mergeProps(array $values): static
    {
        $props = $this->props ?? [];

        foreach ($values as $key => $value) {
            Arr::set($props, $key, $value);
        }

        $this->props = $props;

        return $this;
    }

    public function title(): string
    {
        return (string) $this->prop('title', '');
    }

    public function consentText(): string
    {
        return (string) $this->prop('consent_text', OiLaravelSign::consentText());
    }

    public function branding(): BrandingData
    {
        return BrandingData::from($this->prop('branding', ['name' => config('app.name', 'Laravel')]));
    }

    public function signer(): SignerData
    {
        return new SignerData(
            name: $this->signer_name,
            email: $this->signer_email,
            phone: $this->signer_phone,
            role: $this->signer_role,
        );
    }

    /**
     * The identity the signer typed in before asking for a code.
     */
    public function declaredSigner(): ?SignerData
    {
        $declared = $this->prop('declared');

        if (! is_array($declared)) {
            return null;
        }

        return new SignerData(
            name: trim($declared['first_name'].' '.$declared['last_name']),
            email: $this->signer_email,
            phone: $this->signer_phone,
            role: $declared['role'] ?? null,
            first_name: $declared['first_name'],
            last_name: $declared['last_name'],
        );
    }

    /**
     * @return list<SignatureAttachmentData>
     */
    public function attachments(): array
    {
        return array_values(array_map(
            fn (array $attachment) => SignatureAttachmentData::from($attachment),
            $this->prop('attachments', []),
        ));
    }

    /**
     * Append a step to the evidence trail, with the IP and user agent of the
     * current HTTP request.
     *
     * @param  array<string, mixed>  $props
     */
    public function record(SignatureEventType $type, array $props = []): SignatureEvent
    {
        $request = request();

        /** @var SignatureEvent */
        return $this->events()->create([
            'type' => $type,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'props' => $props === [] ? null : $props,
        ]);
    }

    /**
     * The signed PDF's file name for downloads.
     */
    public function signedFileName(): string
    {
        $slug = str($this->title())->slug()->value() ?: 'document';

        return $slug.'-signed.pdf';
    }

    public function toData(?string $signUrl = null, ?string $token = null): SignatureRequestData
    {
        return SignatureRequestData::fromModel($this, $signUrl, $token);
    }

    public function evidence(): SignatureEvidenceData
    {
        return new SignatureEvidenceData(
            request: $this->toData(),
            consent_text: $this->consentText(),
            attachments: $this->attachments(),
            events: $this->events->map(fn (SignatureEvent $event) => SignatureEventData::fromModel($event))->values()->all(),
            verification: $this->isSigned() ? VerifySignedDocument::handle($this) : null,
        );
    }

    protected static function newFactory(): SignatureRequestFactory
    {
        return SignatureRequestFactory::new();
    }
}
