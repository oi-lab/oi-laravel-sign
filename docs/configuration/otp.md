---
title: "One-Time Codes"
description: "Code length, validity, attempts, lockout and the mail and SMS channels"
section: configuration
order: 2
---

# One-Time Codes

```php
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
    'sms' => ['via' => env('OI_SIGN_SMS_CHANNEL', 'vonage')],
],
```

Codes are stored as an HMAC (keyed by `app.key`), never in clear.

## SMS

`SmsOtpChannel` sends `OtpNotification` on demand through the notification channel named by `otp.sms.via`:

- `vonage` with `laravel/vonage-notification-channel` (`toVonage()`);
- any custom channel: it reads `$notification->toSms($notifiable)` (a string).

## Your own channel

Implement `OiLab\OiLaravelSign\Contracts\OtpChannel` (`send()` and `destination()`, the masked address
shown on the screens and in the trail) and register it under `otp.channels`.
