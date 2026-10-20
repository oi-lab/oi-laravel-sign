<?php

namespace OiLab\OiLaravelSign\Data;

use Carbon\CarbonInterface;
use Spatie\LaravelData\Data;

/**
 * The result of checking a signed PDF: `intact` when the file is byte for
 * byte the one stored at signing, `sealed` when it carries a seal, and
 * `seal_valid` when that seal still matches the bytes it covers. `valid`
 * sums them up.
 */
class SealVerificationData extends Data
{
    public function __construct(
        public readonly bool $valid,
        public readonly bool $intact,
        public readonly bool $sealed,
        public readonly bool $seal_valid,
        public readonly ?string $signer_certificate = null,
        public readonly ?CarbonInterface $sealed_at = null,
        public readonly ?string $error = null,
    ) {}
}
