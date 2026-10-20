<?php

namespace OiLab\OiLaravelSign;

use OiLab\OiLaravelSign\Contracts\OtpChannel;
use OiLab\OiLaravelSign\Contracts\PdfSealer;
use OiLab\OiLaravelSign\Contracts\SignatureDriver;
use OiLab\OiLaravelSign\Enums\OtpChannelType;
use OiLab\OiLaravelSign\Exceptions\SignatureException;
use OiLab\OiLaravelSign\Models\SignatureEvent;
use OiLab\OiLaravelSign\Models\SignatureOtp;
use OiLab\OiLaravelSign\Models\SignatureRequest;
use OiLab\OiLaravelSign\Notifications\OtpNotification;
use OiLab\OiLaravelSign\Notifications\SignatureRequestNotification;
use OiLab\OiLaravelSign\Notifications\SignedDocumentNotification;

/**
 * Static resolver of the configurable classes and settings of the package.
 */
class OiLaravelSign
{
    /**
     * @return class-string<SignatureRequest>
     */
    public static function signatureRequestModel(): string
    {
        return config('oi-laravel-sign.models.signature_request', SignatureRequest::class);
    }

    /**
     * @return class-string<SignatureEvent>
     */
    public static function signatureEventModel(): string
    {
        return config('oi-laravel-sign.models.signature_event', SignatureEvent::class);
    }

    /**
     * @return class-string<SignatureOtp>
     */
    public static function signatureOtpModel(): string
    {
        return config('oi-laravel-sign.models.signature_otp', SignatureOtp::class);
    }

    public static function userModel(): string
    {
        return config('oi-laravel-sign.user_model', 'App\\Models\\User');
    }

    public static function disk(): string
    {
        return (string) config('oi-laravel-sign.disk', 'local');
    }

    public static function directory(): string
    {
        return trim((string) config('oi-laravel-sign.directory', 'signatures'), '/');
    }

    public static function retentionYears(): int
    {
        return (int) config('oi-laravel-sign.retention_years', 10);
    }

    public static function consentText(): string
    {
        return (string) config('oi-laravel-sign.consent_text', 'Bon pour accord');
    }

    public static function defaultOtpChannel(): OtpChannelType
    {
        return OtpChannelType::from((string) config('oi-laravel-sign.otp.channel', 'mail'));
    }

    public static function otpChannel(OtpChannelType $type): OtpChannel
    {
        $class = config('oi-laravel-sign.otp.channels.'.$type->value);

        if (! is_string($class) || ! is_a($class, OtpChannel::class, true)) {
            throw SignatureException::unknownOtpChannel($type->value);
        }

        return app($class);
    }

    public static function driver(): SignatureDriver
    {
        return app(SignatureDriver::class);
    }

    public static function sealer(): PdfSealer
    {
        return app(PdfSealer::class);
    }

    /**
     * @return class-string<SignatureRequestNotification>
     */
    public static function signatureRequestNotification(): string
    {
        return config('oi-laravel-sign.notifications.signature_request', SignatureRequestNotification::class);
    }

    /**
     * @return class-string<OtpNotification>
     */
    public static function otpNotification(): string
    {
        return config('oi-laravel-sign.notifications.otp', OtpNotification::class);
    }

    /**
     * @return class-string<SignedDocumentNotification>
     */
    public static function signedDocumentNotification(): string
    {
        return config('oi-laravel-sign.notifications.signed_document', SignedDocumentNotification::class);
    }

    public static function routesEnabled(): bool
    {
        return (bool) config('oi-laravel-sign.route.enabled', true);
    }

    public static function routeName(string $name): string
    {
        return config('oi-laravel-sign.route.name', 'sign.').$name;
    }

    /**
     * The public signing link of a token, or null when the package routes are
     * off (the host then serves its own screens).
     */
    public static function signUrl(string $token): ?string
    {
        if (! static::routesEnabled()) {
            return null;
        }

        return route(static::routeName('show'), ['token' => $token]);
    }
}
