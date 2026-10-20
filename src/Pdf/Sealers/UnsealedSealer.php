<?php

namespace OiLab\OiLaravelSign\Pdf\Sealers;

use OiLab\OiLaravelSign\Contracts\PdfSealer;
use OiLab\OiLaravelSign\Data\SealVerificationData;
use OiLab\OiLaravelSign\Data\SignatureCertificateData;
use OiLab\OiLaravelSign\Pdf\TcpdfDocumentBuilder;

/**
 * The `none` sealer: appends the certificate page without a cryptographic
 * seal. Integrity then rests on the SHA-256 stored at signing only — for
 * development, not production.
 */
class UnsealedSealer implements PdfSealer
{
    public function __construct(
        protected TcpdfDocumentBuilder $builder,
    ) {}

    public function seal(string $pdf, SignatureCertificateData $certificate): string
    {
        return $this->builder->build($pdf, $certificate)->Output('', 'S');
    }

    public function verify(string $pdf): SealVerificationData
    {
        return new SealVerificationData(valid: true, intact: true, sealed: false, seal_valid: false);
    }
}
