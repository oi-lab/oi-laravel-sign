<?php

namespace OiLab\OiLaravelSign\Data;

use Carbon\CarbonInterface;
use OiLab\OiLaravelSign\Enums\OtpChannelType;
use Spatie\LaravelData\Data;

/**
 * What the "Signature certificate" page appended to the signed PDF states:
 * who signed (declared identity), when (UTC), how the signer was verified,
 * and the fingerprint of exactly what was signed.
 */
class SignatureCertificateData extends Data
{
    public function __construct(
        public readonly string $transaction_id,
        public readonly string $title,
        public readonly string $issuer,
        public readonly SignerData $signer,
        public readonly string $consent_text,
        public readonly OtpChannelType $verification_channel,
        public readonly string $verification_method,
        public readonly string $document_hash,
        public readonly ?string $document_version,
        public readonly CarbonInterface $requested_at,
        public readonly CarbonInterface $signed_at,
        public readonly ?string $ip,
        public readonly ?string $user_agent,
        /** @var list<SignatureAttachmentData> */
        public readonly array $attachments = [],
        /** @var list<SignatureEventData> */
        public readonly array $events = [],
    ) {}
}
