<?php

namespace OiLab\OiLaravelSign\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use OiLab\OiLaravelSign\Database\Factories\SignatureOtpFactory;
use OiLab\OiLaravelSign\Enums\OtpChannelType;
use OiLab\OiLaravelSign\OiLaravelSign;

/**
 * A one-time code sent to the signer, kept as an HMAC of the code.
 *
 * @property int $id
 * @property int $signature_request_id
 * @property string $code_hash
 * @property OtpChannelType $channel
 * @property Carbon $expires_at
 * @property int $attempts
 * @property Carbon|null $consumed_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read SignatureRequest|null $signatureRequest
 */
class SignatureOtp extends Model
{
    /** @use HasFactory<SignatureOtpFactory> */
    use HasFactory;

    protected $table = 'signature_otps';

    /** @var list<string> */
    protected $fillable = [
        'signature_request_id',
        'code_hash',
        'channel',
        'expires_at',
        'attempts',
        'consumed_at',
    ];

    /** @var list<string> */
    protected $hidden = ['code_hash'];

    /** @var array<string, mixed> */
    protected $attributes = [
        'attempts' => 0,
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'signature_request_id' => 'integer',
            'channel' => OtpChannelType::class,
            'expires_at' => 'datetime',
            'attempts' => 'integer',
            'consumed_at' => 'datetime',
        ];
    }

    public static function hashCode(string $code): string
    {
        return hash_hmac('sha256', $code, (string) config('app.key'));
    }

    /**
     * @return BelongsTo<SignatureRequest, $this>
     */
    public function signatureRequest(): BelongsTo
    {
        return $this->belongsTo(OiLaravelSign::signatureRequestModel());
    }

    public function matches(string $code): bool
    {
        return hash_equals($this->code_hash, static::hashCode($code));
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function isUsable(): bool
    {
        return $this->consumed_at === null && ! $this->isExpired();
    }

    protected static function newFactory(): SignatureOtpFactory
    {
        return SignatureOtpFactory::new();
    }
}
