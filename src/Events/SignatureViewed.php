<?php

namespace OiLab\OiLaravelSign\Events;

use Illuminate\Queue\SerializesModels;
use OiLab\OiLaravelSign\Models\SignatureRequest;

/**
 * A signature request was opened by its signer for the first time.
 */
class SignatureViewed
{
    use SerializesModels;

    public function __construct(
        public readonly SignatureRequest $signatureRequest,
    ) {}
}
