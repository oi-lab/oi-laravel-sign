<?php

namespace OiLab\OiLaravelSign\Events;

use Illuminate\Queue\SerializesModels;
use OiLab\OiLaravelSign\Models\SignatureRequest;

/**
 * A signature request was signed: the signed PDF is stored.
 */
class SignatureSigned
{
    use SerializesModels;

    public function __construct(
        public readonly SignatureRequest $signatureRequest,
    ) {}
}
