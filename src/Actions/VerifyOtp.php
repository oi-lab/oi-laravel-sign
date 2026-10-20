<?php

namespace OiLab\OiLaravelSign\Actions;

use OiLab\OiLaravelSign\Enums\SignatureEventType;
use OiLab\OiLaravelSign\Exceptions\SignatureException;
use OiLab\OiLaravelSign\Models\SignatureOtp;
use OiLab\OiLaravelSign\Models\SignatureRequest;

/**
 * Checks the code typed by the signer against the last code sent. A wrong
 * code costs an attempt; the last attempt locks the request.
 */
class VerifyOtp
{
    public static function handle(SignatureRequest $request, string $code): SignatureOtp
    {
        SendOtp::ensureCanProceed($request);

        /** @var SignatureOtp|null $otp */
        $otp = $request->otps()->whereNull('consumed_at')->latest('id')->first();

        if ($otp === null || $otp->isExpired()) {
            throw SignatureException::codeExpired();
        }

        $maxAttempts = (int) config('oi-laravel-sign.otp.max_attempts', 5);

        if (! $otp->matches(trim($code))) {
            $otp->increment('attempts');
            $attemptsLeft = max(0, $maxAttempts - $otp->attempts);

            $request->record(SignatureEventType::OtpFailed, [
                'attempts' => $otp->attempts,
                'attempts_left' => $attemptsLeft,
            ]);

            if ($attemptsLeft === 0) {
                LockSignature::handle($request, 'too_many_attempts');

                throw SignatureException::locked();
            }

            throw SignatureException::invalidCode($attemptsLeft);
        }

        $otp->forceFill(['consumed_at' => now()])->save();

        $request->record(SignatureEventType::OtpVerified, [
            'channel' => $otp->channel->value,
            'attempts' => $otp->attempts + 1,
        ]);

        return $otp;
    }
}
