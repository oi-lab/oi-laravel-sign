<?php

namespace OiLab\OiLaravelSign\Events;

use Illuminate\Queue\SerializesModels;
use OiLab\OiLaravelSign\Models\SignatureRequest;

/**
 * A signature request was cancelled by its issuer.
 */
class SignatureCancelled
{
    use SerializesModels;

    public function __construct(
        public readonly SignatureRequest $signatureRequest,
    ) {}
}
