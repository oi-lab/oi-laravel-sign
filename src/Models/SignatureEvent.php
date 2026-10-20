<?php

namespace OiLab\OiLaravelSign\Models;

use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use OiLab\OiLaravelSign\Data\SignatureEventData;
use OiLab\OiLaravelSign\Database\Factories\SignatureEventFactory;
use OiLab\OiLaravelSign\Enums\SignatureEventType;
use OiLab\OiLaravelSign\Observers\SignatureEventObserver;
use OiLab\OiLaravelSign\OiLaravelSign;

/**
 * One timestamped step of a request's evidence trail. The table is append
 * only: an event is never updated nor deleted on its own.
 *
 * @property int $id
 * @property string $uuid
 * @property int $signature_request_id
 * @property SignatureEventType $type
 * @property string|null $ip
 * @property string|null $user_agent
 * @property array<string, mixed>|null $props
 * @property Carbon|null $created_at
 * @property-read SignatureRequest|null $signatureRequest
 */
#[ObservedBy(SignatureEventObserver::class)]
class SignatureEvent extends Model
{
    /** @use HasFactory<SignatureEventFactory> */
    use HasFactory;

    use HasUuids;

    public const UPDATED_AT = null;

    protected $table = 'signature_events';

    /** @var list<string> */
    protected $fillable = [
        'signature_request_id',
        'type',
        'ip',
        'user_agent',
        'props',
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
            'signature_request_id' => 'integer',
            'type' => SignatureEventType::class,
            'props' => 'array',
        ];
    }

    /**
     * @return BelongsTo<SignatureRequest, $this>
     */
    public function signatureRequest(): BelongsTo
    {
        return $this->belongsTo(OiLaravelSign::signatureRequestModel());
    }

    public function toData(): SignatureEventData
    {
        return SignatureEventData::fromModel($this);
    }

    protected static function newFactory(): SignatureEventFactory
    {
        return SignatureEventFactory::new();
    }
}
