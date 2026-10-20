<?php

namespace OiLab\OiLaravelSign\Drivers;

use OiLab\OiLaravelSign\Contracts\PdfSealer;
use OiLab\OiLaravelSign\Contracts\SignatureDriver;
use OiLab\OiLaravelSign\Data\SealVerificationData;
use OiLab\OiLaravelSign\Data\SignatureCertificateData;
use OiLab\OiLaravelSign\Models\SignatureRequest;

/**
 * Signs in the package (simple electronic signature): the signer was
 * verified by a one-time code, the configured sealer seals the result.
 */
class NativeSignatureDriver implements SignatureDriver
{
    public function __construct(
        protected PdfSealer $sealer,
    ) {}

    public function sign(SignatureRequest $request, string $pdf, SignatureCertificateData $certificate): string
    {
        return $this->sealer->seal($pdf, $certificate);
    }

    public function verify(SignatureRequest $request, string $signedPdf): SealVerificationData
    {
        return $this->sealer->verify($signedPdf);
    }
}
