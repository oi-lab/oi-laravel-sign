<?php

namespace OiLab\OiLaravelSign\Pdf\Sealers;

use OiLab\OiLaravelSign\Contracts\PdfSealer;
use OiLab\OiLaravelSign\Data\SealVerificationData;
use OiLab\OiLaravelSign\Data\SignatureCertificateData;
use OiLab\OiLaravelSign\Exceptions\SignatureException;
use OiLab\OiLaravelSign\Pdf\PdfSignatureInspector;
use OiLab\OiLaravelSign\Pdf\TcpdfDocumentBuilder;

/**
 * Seals the signed PDF with the server's certificate: a PAdES-Basic
 * signature (ISO 32000-1, PKCS#7 detached, `adbe.pkcs7.detached`) covering
 * the document and its certificate page, which PDF readers check.
 */
class PadesSealer implements PdfSealer
{
    public function __construct(
        protected TcpdfDocumentBuilder $builder,
        protected PdfSignatureInspector $inspector,
    ) {}

    public function seal(string $pdf, SignatureCertificateData $certificate): string
    {
        $credentials = $this->credentials();
        $document = $this->builder->build($pdf, $certificate);
        $extraCertificates = null;

        if ($credentials['extra'] !== []) {
            $extraCertificates = tempnam(sys_get_temp_dir(), 'oisign');
            file_put_contents($extraCertificates, implode("\n", $credentials['extra']));
        }

        try {
            $document->setSignature(
                $credentials['certificate'],
                $credentials['key'],
                $credentials['password'],
                $extraCertificates ?? '',
                2,
                array_filter([
                    'Name' => $certificate->issuer,
                    'Location' => config('oi-laravel-sign.sealer.location'),
                    'Reason' => config('oi-laravel-sign.sealer.reason') ?: $certificate->consent_text,
                    'ContactInfo' => config('oi-laravel-sign.sealer.contact'),
                ]),
            );
            $document->setSignatureAppearance(0, 0, 0, 0);

            return $document->Output('', 'S');
        } finally {
            if ($extraCertificates !== null) {
                @unlink($extraCertificates);
            }
        }
    }

    public function verify(string $pdf): SealVerificationData
    {
        return $this->inspector->inspect($pdf);
    }

    /**
     * @return array{certificate: string, key: string, password: string, extra: list<string>}
     */
    protected function credentials(): array
    {
        $path = config('oi-laravel-sign.sealer.certificate');
        $password = (string) config('oi-laravel-sign.sealer.password', '');

        if (blank($path) || ! is_readable($path)) {
            throw SignatureException::sealerNotConfigured('set OI_SIGN_CERTIFICATE_PATH to a readable certificate (or run sign:make-certificate).');
        }

        $contents = (string) file_get_contents($path);
        $extra = [];

        if (in_array(strtolower(pathinfo($path, PATHINFO_EXTENSION)), ['p12', 'pfx'], true)) {
            if (! openssl_pkcs12_read($contents, $bundle, $password)) {
                throw SignatureException::sealerNotConfigured('the PKCS#12 bundle cannot be opened with OI_SIGN_CERTIFICATE_PASSWORD.');
            }

            return [
                'certificate' => $bundle['cert'],
                'key' => $bundle['pkey'],
                'password' => '',
                'extra' => array_values($bundle['extracerts'] ?? []),
            ];
        }

        $keyPath = config('oi-laravel-sign.sealer.private_key');

        if (blank($keyPath) || ! is_readable($keyPath)) {
            throw SignatureException::sealerNotConfigured('set OI_SIGN_PRIVATE_KEY_PATH to the readable private key of the certificate.');
        }

        $extraPath = config('oi-laravel-sign.sealer.extra_certificates');

        if (filled($extraPath) && is_readable($extraPath)) {
            $extra[] = (string) file_get_contents($extraPath);
        }

        return [
            'certificate' => $contents,
            'key' => (string) file_get_contents($keyPath),
            'password' => $password,
            'extra' => $extra,
        ];
    }
}
