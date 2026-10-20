<?php

namespace OiLab\OiLaravelSign\Events;

use Illuminate\Queue\SerializesModels;
use OiLab\OiLaravelSign\Models\SignatureRequest;

/**
 * A signature request expired: past its date (`expired`), its document
 * changed (`document_modified`), or the model can no longer be signed
 * (`not_signable`).
 */
class SignatureExpired
{
    use SerializesModels;

    public function __construct(
        public readonly SignatureRequest $signatureRequest,
        public readonly string $reason,
    ) {}
}
