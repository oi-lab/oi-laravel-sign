<?php

namespace OiLab\OiLaravelSign\Actions;

use OiLab\OiLaravelSign\Data\SealVerificationData;
use OiLab\OiLaravelSign\Models\SignatureRequest;
use OiLab\OiLaravelSign\OiLaravelSign;
use OiLab\OiLaravelSign\Support\SignatureFiles;

/**
 * Checks the stored signed PDF of a request: byte for byte the file written
 * at signing, and its seal still valid.
 */
class VerifySignedDocument
{
    public static function handle(SignatureRequest $request, ?string $pdf = null): SealVerificationData
    {
        $pdf ??= $request->signed_pdf_path === null ? null : SignatureFiles::get($request->signed_pdf_path);

        if ($pdf === null) {
            return new SealVerificationData(
                valid: false,
                intact: false,
                sealed: false,
                seal_valid: false,
                error: 'The signed PDF is missing.',
            );
        }

        $expectedHash = (string) $request->prop('signed_pdf_hash', '');
        $intact = $expectedHash !== '' && hash_equals($expectedHash, hash('sha256', $pdf));
        $seal = OiLaravelSign::driver()->verify($request, $pdf);

        return new SealVerificationData(
            valid: $intact && (! $seal->sealed || $seal->seal_valid),
            intact: $intact,
            sealed: $seal->sealed,
            seal_valid: $seal->seal_valid,
            signer_certificate: $seal->signer_certificate,
            sealed_at: $seal->sealed_at,
            error: $intact ? $seal->error : 'The signed PDF differs from the file stored at signing.',
        );
    }
}
