<?php

namespace OiLab\OiLaravelSign\Otp;

use Illuminate\Support\Facades\Notification;
use OiLab\OiLaravelSign\Contracts\OtpChannel;
use OiLab\OiLaravelSign\Models\SignatureRequest;
use OiLab\OiLaravelSign\OiLaravelSign;

/**
 * Mails the code to the signer's address.
 */
class MailOtpChannel implements OtpChannel
{
    public function send(SignatureRequest $request, string $code): void
    {
        $notification = OiLaravelSign::otpNotification();

        Notification::route('mail', [$request->signer_email => $request->signer_name])
            ->notify(new $notification($request, $code, 'mail'));
    }

    public function destination(SignatureRequest $request): string
    {
        [$local, $domain] = array_pad(explode('@', $request->signer_email, 2), 2, '');

        return mb_substr($local, 0, 1).'***@'.$domain;
    }
}
