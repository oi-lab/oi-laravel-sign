<?php

namespace OiLab\OiLaravelSign\Data;

use Carbon\CarbonInterface;
use OiLab\OiLaravelSign\Enums\SignatureEventType;
use OiLab\OiLaravelSign\Models\SignatureEvent;
use Spatie\LaravelData\Data;

/**
 * One timestamped step of the evidence trail.
 */
class SignatureEventData extends Data
{
    public function __construct(
        public readonly string $uuid,
        public readonly SignatureEventType $type,
        public readonly string $label,
        public readonly ?string $ip,
        public readonly ?string $user_agent,
        /** @var array<string, mixed> */
        public readonly array $props,
        public readonly ?CarbonInterface $created_at,
    ) {}

    public static function fromModel(SignatureEvent $event): self
    {
        return new self(
            uuid: $event->uuid,
            type: $event->type,
            label: $event->type->label(),
            ip: $event->ip,
            user_agent: $event->user_agent,
            props: $event->props ?? [],
            created_at: $event->created_at?->utc(),
        );
    }
}
