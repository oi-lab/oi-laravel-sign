<?php

namespace OiLab\OiLaravelSign\Contracts;

use OiLab\OiLaravelSign\Models\SignatureRequest;

/**
 * Delivers the one-time code of a request to its signer.
 */
interface OtpChannel
{
    public function send(SignatureRequest $request, string $code): void;

    /**
     * Where the code goes, masked for the evidence trail and the screens
     *
     * ("j***@example.com", "******78").
     */
    public function destination(SignatureRequest $request): string;
}
