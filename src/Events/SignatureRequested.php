<?php

namespace OiLab\OiLaravelSign\Events;

use Illuminate\Queue\SerializesModels;
use OiLab\OiLaravelSign\Models\SignatureRequest;

/**
 * A signature request was created and sent to its signer.
 */
class SignatureRequested
{
    use SerializesModels;

    public function __construct(
        public readonly SignatureRequest $signatureRequest,
    ) {}
}
