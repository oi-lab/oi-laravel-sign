<?php

namespace OiLab\OiLaravelSign\Data;

use Spatie\LaravelData\Data;

/**
 * The full evidence file of a request, ready to export: the request, the
 * fingerprints of every stored file and the complete event trail.
 */
class SignatureEvidenceData extends Data
{
    public function __construct(
        public readonly SignatureRequestData $request,
        public readonly string $consent_text,
        /** @var list<SignatureAttachmentData> */
        public readonly array $attachments,
        /** @var list<SignatureEventData> */
        public readonly array $events,
        public readonly ?SealVerificationData $verification,
    ) {}
}
