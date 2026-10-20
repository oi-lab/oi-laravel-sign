<?php

namespace OiLab\OiLaravelSign\Data;

use Spatie\LaravelData\Data;

/**
 * How the issuer of a document dresses the signing screens: its name, logo
 * and accent color (a CSS color, e.g. `#2563eb`).
 */
class BrandingData extends Data
{
    public function __construct(
        public readonly string $name,
        public readonly ?string $logo_url = null,
        public readonly ?string $color = null,
    ) {}
}
