<?php

namespace OiLab\OiLaravelSign\Pdf;

use Illuminate\Support\Carbon;
use OiLab\OiLaravelSign\Data\SealVerificationData;

/**
 * Checks the last signature of a PDF: its byte range must cover the whole
 * file (nothing appended after signing) and its PKCS#7 / CMS signature must
 * still match the bytes it covers.
 */
class PdfSignatureInspector
{
    public function inspect(string $pdf): SealVerificationData
    {
        if (! preg_match_all('/\/ByteRange\s*\[\s*(\d+)\s+(\d+)\s+(\d+)\s+(\d+)\s*\]/', $pdf, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE)) {
            return new SealVerificationData(valid: false, intact: true, sealed: false, seal_valid: false, error: 'The PDF carries no seal.');
        }

        $match = end($matches);
        [$start1, $length1, $start2, $length2] = array_map(fn (array $group) => (int) $group[0], array_slice($match, 1));

        if ($start1 !== 0 || $start2 + $length2 !== strlen($pdf) || $start2 <= $length1) {
            return $this->invalid('The PDF was changed after it was sealed.');
        }

        $hex = trim(substr($pdf, $length1, $start2 - $length1), "<> \n\r");
        $signature = $this->der(@hex2bin($hex) ?: '');

        if ($signature === null) {
            return $this->invalid('The seal cannot be read.');
        }

        $signedBytes = substr($pdf, 0, $length1).substr($pdf, $start2, $length2);

        [$valid, $subject] = $this->verifyCms($signedBytes, $signature);

        return new SealVerificationData(
            valid: $valid,
            intact: true,
            sealed: true,
            seal_valid: $valid,
            signer_certificate: $subject,
            sealed_at: $this->signingTime($pdf, (int) $match[0][1]),
            error: $valid ? null : 'The seal does not match the document.',
        );
    }

    protected function invalid(string $error): SealVerificationData
    {
        return new SealVerificationData(valid: false, intact: true, sealed: true, seal_valid: false, error: $error);
    }

    /**
     * Trim the zero padding TCPDF leaves after the DER structure.
     */
    protected function der(string $bytes): ?string
    {
        if (strlen($bytes) < 2 || ord($bytes[0]) !== 0x30) {
            return null;
        }

        $length = ord($bytes[1]);
        $offset = 2;

        if ($length & 0x80) {
            $count = $length & 0x7F;
            $length = 0;

            for ($i = 0; $i < $count; $i++) {
                $length = ($length << 8) | ord($bytes[2 + $i]);
            }

            $offset += $count;
        }

        return $offset + $length <= strlen($bytes) ? substr($bytes, 0, $offset + $length) : null;
    }

    /**
     * @return array{0: bool, 1: string|null}
     */
    protected function verifyCms(string $content, string $signature): array
    {
        $contentFile = tempnam(sys_get_temp_dir(), 'oisign');
        $signatureFile = tempnam(sys_get_temp_dir(), 'oisign');
        $signerFile = tempnam(sys_get_temp_dir(), 'oisign');

        try {
            file_put_contents($contentFile, $content);
            file_put_contents($signatureFile, $signature);

            $valid = openssl_cms_verify(
                $contentFile,
                OPENSSL_CMS_DETACHED | OPENSSL_CMS_BINARY | OPENSSL_CMS_NOVERIFY,
                $signerFile,
                [],
                null,
                null,
                null,
                $signatureFile,
                OPENSSL_ENCODING_DER,
            ) === true;

            while (openssl_error_string() !== false) {
                // Drain the OpenSSL error queue so it does not leak into later calls.
            }

            $subject = null;
            $certificate = $valid ? openssl_x509_parse((string) file_get_contents($signerFile)) : false;

            if (is_array($certificate)) {
                $subject = $certificate['name'] ?? null;
            }

            return [$valid, $subject];
        } finally {
            @unlink($contentFile);
            @unlink($signatureFile);
            @unlink($signerFile);
        }
    }

    protected function signingTime(string $pdf, int $byteRangeOffset): ?Carbon
    {
        $dictionary = substr($pdf, max(0, $byteRangeOffset - 2000), 4000);

        if (! preg_match('/\/M\s*\(D:(\d{14})([Z+\-])?(\d{2})?\'?(\d{2})?/', $dictionary, $date)) {
            return null;
        }

        $offset = ($date[2] ?? 'Z') === 'Z' || ! isset($date[3]) ? '+00:00' : $date[2].$date[3].':'.($date[4] ?? '00');

        return Carbon::createFromFormat('YmdHisP', $date[1].$offset)?->utc();
    }
}
