<?php

namespace OiLab\OiLaravelSign\Exceptions;

use OiLab\OiLaravelSign\Enums\SignatureStatus;
use RuntimeException;

/**
 * Everything that stops a signature step. The message is safe to show the
 * signer; `reason` is a stable machine-readable key.
 */
class SignatureException extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly string $reason,
    ) {
        parent::__construct($message);
    }

    public static function notSignable(): self
    {
        return new self(__('oi-laravel-sign::sign.error.not_signable'), 'not_signable');
    }

    public static function notOpen(SignatureStatus $status): self
    {
        return new self(__('oi-laravel-sign::sign.error.not_open', ['status' => $status->label()]), 'not_open');
    }

    public static function locked(): self
    {
        return new self(__('oi-laravel-sign::sign.error.locked'), 'locked');
    }

    public static function identityMissing(): self
    {
        return new self(__('oi-laravel-sign::sign.error.identity_missing'), 'identity_missing');
    }

    public static function invalidCode(int $attemptsLeft): self
    {
        return new self(trans_choice('oi-laravel-sign::sign.error.invalid_code', $attemptsLeft, ['count' => $attemptsLeft]), 'invalid_code');
    }

    public static function codeExpired(): self
    {
        return new self(__('oi-laravel-sign::sign.error.code_expired'), 'code_expired');
    }

    public static function documentModified(): self
    {
        return new self(__('oi-laravel-sign::sign.error.document_modified'), 'document_modified');
    }

    public static function missingPhone(): self
    {
        return new self('An SMS code needs the signer\'s phone number.', 'missing_phone');
    }

    public static function unknownOtpChannel(string $channel): self
    {
        return new self("No OTP channel is configured for [{$channel}].", 'unknown_channel');
    }

    public static function sealerNotConfigured(string $detail): self
    {
        return new self("The PDF sealer is not configured: {$detail}", 'sealer_not_configured');
    }

    public static function unreadablePdf(string $detail): self
    {
        return new self("The PDF cannot be read: {$detail}", 'unreadable_pdf');
    }

    public static function fileExists(string $path): self
    {
        return new self("Signature files are written once: [{$path}] already exists.", 'file_exists');
    }

    public static function retained(): self
    {
        return new self('A signed request is kept for its retention period and cannot be deleted for good yet.', 'retained');
    }
}
