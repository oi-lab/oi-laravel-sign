<?php

namespace OiLab\OiLaravelSign\Data;

use Spatie\LaravelData\Attributes\Hidden;
use Spatie\LaravelData\Data;

/**
 * An attachment as stored with a request, with the SHA-256 of its contents.
 */
class SignatureAttachmentData extends Data
{
    public function __construct(
        public readonly string $name,
        public readonly string $mime_type,
        public readonly int $size,
        public readonly string $hash,
        #[Hidden]
        public readonly string $path,
    ) {}
}
