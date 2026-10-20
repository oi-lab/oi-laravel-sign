<?php

namespace OiLab\OiLaravelSign\Data;

use Spatie\LaravelData\Attributes\Validation\Email;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Data;

/**
 * The person asked to sign. On a signed request's certificate it is the
 * identity the signer declared (first and last name, role) along with the
 * address the code was sent to.
 */
class SignerData extends Data
{
    public function __construct(
        #[Max(255)]
        public readonly string $name,
        #[Email, Max(255)]
        public readonly string $email,
        #[Max(32)]
        public readonly ?string $phone = null,
        #[Max(255)]
        public readonly ?string $role = null,
        public readonly ?string $first_name = null,
        public readonly ?string $last_name = null,
    ) {}
}
