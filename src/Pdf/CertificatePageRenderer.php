<?php

namespace OiLab\OiLaravelSign\Pdf;

use OiLab\OiLaravelSign\Data\SignatureCertificateData;

/**
 * Renders the "Signature certificate" page as the HTML subset TCPDF
 * understands (publish the `oi-laravel-sign::pdf.certificate` view to change
 * it).
 */
class CertificatePageRenderer
{
    public function render(SignatureCertificateData $certificate): string
    {
        return view('oi-laravel-sign::pdf.certificate', ['certificate' => $certificate])->render();
    }
}
