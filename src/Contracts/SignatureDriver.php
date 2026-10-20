<?php

namespace OiLab\OiLaravelSign\Contracts;

use OiLab\OiLaravelSign\Data\SealVerificationData;
use OiLab\OiLaravelSign\Data\SignatureCertificateData;
use OiLab\OiLaravelSign\Models\SignatureRequest;

/**
 * Produces the signed document of a request once its signer is verified.
 * The `native` driver signs in the package; a provider driver (Yousign…)
 * would hand the document to a third party instead.
 */
interface SignatureDriver
{
    /**
     * @return string The bytes of the signed PDF
     */
    public function sign(SignatureRequest $request, string $pdf, SignatureCertificateData $certificate): string;

    public function verify(SignatureRequest $request, string $signedPdf): SealVerificationData;
}
