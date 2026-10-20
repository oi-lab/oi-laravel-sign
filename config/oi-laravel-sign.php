<?php

use OiLab\OiLaravelSign\Models\SignatureEvent;
use OiLab\OiLaravelSign\Models\SignatureOtp;
use OiLab\OiLaravelSign\Models\SignatureRequest;
use OiLab\OiLaravelSign\Notifications\OtpNotification;
use OiLab\OiLaravelSign\Notifications\SignatureRequestNotification;
use OiLab\OiLaravelSign\Notifications\SignedDocumentNotification;
use OiLab\OiLaravelSign\Otp\MailOtpChannel;
use OiLab\OiLaravelSign\Otp\SmsOtpChannel;

return [

    /*
    |--------------------------------------------------------------------------
    | Models
    |--------------------------------------------------------------------------
    |
    | Every class is resolved at runtime through OiLaravelSign, so a host app
    | can swap a model for its own subclass.
    |
    */

    'user_model' => 'App\\Models\\User',

    'models' => [
        'signature_request' => SignatureRequest::class,
        'signature_event' => SignatureEvent::class,
        'signature_otp' => SignatureOtp::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Public signing routes
    |--------------------------------------------------------------------------
    |
    | The signer opens /{prefix}/{token} without an account. The token is long,
    | random and stored hashed; `rate_limit` feeds the `oi-sign` limiter used
    | by the `throttle:oi-sign` middleware. `gate`, when set, is checked by a
    | `can:` middleware in front of every route.
    |
    */

    'route' => [
        'enabled' => env('OI_SIGN_ROUTES', true),
        'prefix' => 'sign',
        'name' => 'sign.',
        'middleware' => ['web', 'throttle:oi-sign'],
        'gate' => null,
        'rate_limit' => env('OI_SIGN_RATE_LIMIT', 30),
    ],

    /*
    |--------------------------------------------------------------------------
    | Storage
    |--------------------------------------------------------------------------
    |
    | The document to sign, its attachments and the signed PDF are written
    | once under {directory}/{uuid}/ and never overwritten.
    |
    */

    'disk' => env('OI_SIGN_DISK', env('FILESYSTEM_DISK', 'local')),

    'directory' => env('OI_SIGN_DIRECTORY', 'signatures'),

    'retention_years' => env('OI_SIGN_RETENTION_YEARS', 10),

    /*
    |--------------------------------------------------------------------------
    | Signature requests
    |--------------------------------------------------------------------------
    */

    'expires_in_days' => env('OI_SIGN_EXPIRES_IN_DAYS', 30),

    'consent_text' => env('OI_SIGN_CONSENT_TEXT', 'Bon pour accord'),

    /*
    |--------------------------------------------------------------------------
    | One-time code
    |--------------------------------------------------------------------------
    |
    | `channel` is the default channel of a request (mail or sms). After
    | `max_attempts` wrong codes, or `max_sends` codes sent, the request is
    | locked for `lockout_minutes`. `sms.via` is the notification channel the
    | SMS goes through (vonage, or the name of a custom channel reading
    | toSms()).
    |
    */

    'otp' => [
        'channel' => env('OI_SIGN_OTP_CHANNEL', 'mail'),
        'length' => 6,
        'ttl_minutes' => 10,
        'max_attempts' => 5,
        'max_sends' => 5,
        'lockout_minutes' => 15,
        'channels' => [
            'mail' => MailOtpChannel::class,
            'sms' => SmsOtpChannel::class,
        ],
        'sms' => [
            'via' => env('OI_SIGN_SMS_CHANNEL', 'vonage'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Signature driver and PDF sealer
    |--------------------------------------------------------------------------
    |
    | The `native` driver signs in the package: the signed PDF is the original
    | document plus a "Signature certificate" page, sealed by the sealer.
    |
    | Sealers: `pades` seals with a server certificate (PAdES-Basic, PKCS#7
    | detached, as read by Acrobat); `none` appends the certificate page
    | without a cryptographic seal (development only). Both accept a class
    | name implementing PdfSealer instead.
    |
    | `certificate` is a PEM certificate (with `private_key`) or a PKCS#12
    | bundle (.p12/.pfx, the key inside). `php artisan sign:make-certificate`
    | creates a self-signed one for development.
    |
    */

    'driver' => env('OI_SIGN_DRIVER', 'native'),

    'sealer' => [
        'driver' => env('OI_SIGN_SEALER', 'pades'),
        'certificate' => env('OI_SIGN_CERTIFICATE_PATH'),
        'private_key' => env('OI_SIGN_PRIVATE_KEY_PATH'),
        'password' => env('OI_SIGN_CERTIFICATE_PASSWORD', ''),
        'extra_certificates' => env('OI_SIGN_EXTRA_CERTIFICATES_PATH'),
        'reason' => env('OI_SIGN_SEAL_REASON', 'Electronic signature'),
        'location' => env('OI_SIGN_SEAL_LOCATION'),
        'contact' => env('OI_SIGN_SEAL_CONTACT'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Notifications
    |--------------------------------------------------------------------------
    |
    | Swap a class for your own to change the wording or the channels.
    | `signed_document` mails the signed PDF to the signer once signed.
    |
    */

    'notifications' => [
        'signature_request' => SignatureRequestNotification::class,
        'otp' => OtpNotification::class,
        'signed_document' => SignedDocumentNotification::class,
        'send_signed_document' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Expiration
    |--------------------------------------------------------------------------
    |
    | Schedules `sign:expire` hourly: open requests past their date, or whose
    | document changed, are expired.
    |
    */

    'expiration' => [
        'enabled' => env('OI_SIGN_EXPIRATION', true),
    ],

];
