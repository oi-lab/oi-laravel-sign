<?php

namespace OiLab\OiLaravelSign\Data;

use Spatie\LaravelData\Data;

/**
 * A file shown to the signer next to the document (terms and conditions…).
 * Its contents are copied when the request is created, so the signer reads
 * the version that was sent.
 */
class SignableAttachmentData extends Data
{
    public function __construct(
        public readonly string $name,
        public readonly string $contents,
        public readonly string $mime_type = 'application/pdf',
    ) {}
}
