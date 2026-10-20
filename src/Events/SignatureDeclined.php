<?php

namespace OiLab\OiLaravelSign\Events;

use Illuminate\Queue\SerializesModels;
use OiLab\OiLaravelSign\Models\SignatureRequest;

/**
 * A signature request was declined by its signer (see decline_reason).
 */
class SignatureDeclined
{
    use SerializesModels;

    public function __construct(
        public readonly SignatureRequest $signatureRequest,
    ) {}
}
