<?php

namespace OiLab\OiLaravelSign\Data;

use Carbon\CarbonInterface;
use OiLab\OiLaravelSign\Enums\OtpChannelType;
use OiLab\OiLaravelSign\Enums\SignatureStatus;
use OiLab\OiLaravelSign\Models\SignatureRequest;
use Spatie\LaravelData\Data;

/**
 * A signature request. `sign_url` carries the signing link only right after
 * the request was created or resent: the token is stored hashed, so the link
 * cannot be rebuilt later.
 */
class SignatureRequestData extends Data
{
    public function __construct(
        public readonly int $id,
        public readonly string $uuid,
        public readonly SignatureStatus $status,
        public readonly string $status_label,
        public readonly string $signable_type,
        public readonly int|string $signable_id,
        public readonly ?string $owner_type,
        public readonly int|string|null $owner_id,
        public readonly string $title,
        public readonly SignerData $signer,
        public readonly ?SignerData $declared_signer,
        public readonly string $document_hash,
        public readonly ?string $document_version,
        public readonly OtpChannelType $otp_channel,
        public readonly ?CarbonInterface $expires_at,
        public readonly ?CarbonInterface $viewed_at,
        public readonly ?CarbonInterface $signed_at,
        public readonly ?string $decline_reason,
        public readonly bool $has_signed_pdf,
        public readonly ?string $signed_pdf_hash,
        public readonly ?string $sign_url,
        public readonly ?string $token,
        public readonly ?CarbonInterface $created_at,
        public readonly ?CarbonInterface $updated_at,
    ) {}

    public static function fromModel(SignatureRequest $request, ?string $signUrl = null, ?string $token = null): self
    {
        return new self(
            id: $request->id,
            uuid: $request->uuid,
            status: $request->status,
            status_label: $request->status->label(),
            signable_type: $request->signable_type,
            signable_id: $request->signable_id,
            owner_type: $request->owner_type,
            owner_id: $request->owner_id,
            title: $request->title(),
            signer: $request->signer(),
            declared_signer: $request->declaredSigner(),
            document_hash: $request->document_hash,
            document_version: $request->document_version,
            otp_channel: $request->otp_channel,
            expires_at: $request->expires_at,
            viewed_at: $request->viewed_at,
            signed_at: $request->signed_at,
            decline_reason: $request->decline_reason,
            has_signed_pdf: $request->signed_pdf_path !== null,
            signed_pdf_hash: $request->prop('signed_pdf_hash'),
            sign_url: $signUrl,
            token: $token,
            created_at: $request->created_at,
            updated_at: $request->updated_at,
        );
    }
}
