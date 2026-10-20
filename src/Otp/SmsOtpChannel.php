<?php

namespace OiLab\OiLaravelSign\Otp;

use Illuminate\Support\Facades\Notification;
use OiLab\OiLaravelSign\Contracts\OtpChannel;
use OiLab\OiLaravelSign\Exceptions\SignatureException;
use OiLab\OiLaravelSign\Models\SignatureRequest;
use OiLab\OiLaravelSign\OiLaravelSign;

/**
 * Texts the code to the signer's phone through the notification channel
 * named by `otp.sms.via` (vonage, or a custom channel reading toSms()).
 */
class SmsOtpChannel implements OtpChannel
{
    public function send(SignatureRequest $request, string $code): void
    {
        if (blank($request->signer_phone)) {
            throw SignatureException::missingPhone();
        }

        $via = (string) config('oi-laravel-sign.otp.sms.via', 'vonage');
        $notification = OiLaravelSign::otpNotification();

        Notification::route($via, $request->signer_phone)
            ->notify(new $notification($request, $code, $via));
    }

    public function destination(SignatureRequest $request): string
    {
        $phone = preg_replace('/\D+/', '', (string) $request->signer_phone);

        return str_repeat('*', max(0, strlen($phone) - 2)).substr($phone, -2);
    }
}
