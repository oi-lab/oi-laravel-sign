<?php

namespace OiLab\OiLaravelSign\Pdf;

use RuntimeException;

/**
 * Creates a self-signed seal certificate and its private key (PEM), for
 * development and tests. Production seals use a certificate issued by a
 * trusted authority.
 */
class SealCertificateGenerator
{
    /**
     * @return array{certificate: string, private_key: string}
     */
    public function generate(string $commonName, ?string $organization = null, int $days = 3650): array
    {
        $key = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);

        if ($key === false) {
            throw new RuntimeException('OpenSSL could not create a private key.');
        }

        $subject = array_filter(['commonName' => $commonName, 'organizationName' => $organization]);
        $request = openssl_csr_new($subject, $key, ['digest_alg' => 'sha256']);
        $certificate = $request === false || $request === true ? false : openssl_csr_sign($request, null, $key, $days, ['digest_alg' => 'sha256']);

        if ($certificate === false || ! openssl_x509_export($certificate, $certificatePem) || ! openssl_pkey_export($key, $keyPem)) {
            throw new RuntimeException('OpenSSL could not create the certificate.');
        }

        return ['certificate' => $certificatePem, 'private_key' => $keyPem];
    }

    /**
     * @return array{certificate: string, private_key: string} The written paths
     */
    public function write(string $directory, string $commonName, ?string $organization = null, int $days = 3650): array
    {
        $pair = $this->generate($commonName, $organization, $days);

        if (! is_dir($directory)) {
            mkdir($directory, 0700, true);
        }

        $paths = [
            'certificate' => rtrim($directory, '/').'/seal-certificate.pem',
            'private_key' => rtrim($directory, '/').'/seal-private-key.pem',
        ];

        file_put_contents($paths['certificate'], $pair['certificate']);
        file_put_contents($paths['private_key'], $pair['private_key']);
        chmod($paths['private_key'], 0600);

        return $paths;
    }
}
