<?php

namespace OiLab\OiLaravelSign\Contracts;

use OiLab\OiLaravelSign\Data\BrandingData;
use OiLab\OiLaravelSign\Data\SignableAttachmentData;

/**
 * A model a signer can sign online (a quote, a contract…). The package knows
 * nothing else about it.
 */
interface Signable
{
    /**
     * The label shown to the signer ("Quote Q-2026-042").
     */
    public function signableTitle(): string;

    /**
     * The exact PDF bytes to sign. They are copied when a request is created;
     * the signer reads and signs that copy.
     */
    public function signablePdf(): string;

    /**
     * Files shown next to the document (terms and conditions…).
     *
     * @return list<SignableAttachmentData>
     */
    public function signableAttachments(): array;

    /**
     * The business rule: false once the model expired, was cancelled or was
     * already signed.
     */
    public function canBeSigned(): bool;

    /**
     * A version number or fingerprint that changes whenever the document
     * changes: a request made on another version can no longer be signed.
     */
    public function signableVersion(): ?string;

    /**
     * The issuer's name, logo and color for the signing screens.
     */
    public function signableBranding(): BrandingData;
}
