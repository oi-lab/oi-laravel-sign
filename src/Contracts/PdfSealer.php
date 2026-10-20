<?php

namespace OiLab\OiLaravelSign\Contracts;

use OiLab\OiLaravelSign\Data\SealVerificationData;
use OiLab\OiLaravelSign\Data\SignatureCertificateData;

/**
 * The boundary with the PDF libraries: appends the certificate page to the
 * signed document, seals the result, and checks a seal later.
 */
interface PdfSealer
{
    /**
     * @return string The bytes of the signed PDF
     */
    public function seal(string $pdf, SignatureCertificateData $certificate): string;

    public function verify(string $pdf): SealVerificationData;
}
