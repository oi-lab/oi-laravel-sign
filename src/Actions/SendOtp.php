<?php

namespace OiLab\OiLaravelSign\Actions;

use OiLab\OiLaravelSign\Enums\SignatureEventType;
use OiLab\OiLaravelSign\Exceptions\SignatureException;
use OiLab\OiLaravelSign\Models\SignatureOtp;
use OiLab\OiLaravelSign\Models\SignatureRequest;
use OiLab\OiLaravelSign\OiLaravelSign;

/**
 * Records the identity the signer declared and their consent, then sends a
 * fresh one-time code (any earlier code stops working).
 */
class SendOtp
{
    /**
     * @param  array{first_name: string, last_name: string, role?: string|null}|null  $identity  Null to resend a code for the identity already declared
     */
    public static function handle(SignatureRequest $request, ?array $identity = null): SignatureOtp
    {
        static::ensureCanProceed($request);

        if ($identity !== null) {
            $request->mergeProps(['declared' => [
                'first_name' => trim($identity['first_name']),
                'last_name' => trim($identity['last_name']),
                'role' => filled($identity['role'] ?? null) ? trim($identity['role']) : null,
                'consent_text' => $request->consentText(),
                'consented_at' => now()->utc()->toIso8601String(),
            ]]);
        }

        if ($request->prop('declared') === null) {
            throw SignatureException::identityMissing();
        }

        $sends = (int) $request->prop('otp_sends', 0) + 1;

        if ($sends > (int) config('oi-laravel-sign.otp.max_sends', 5)) {
            LockSignature::handle($request, 'too_many_codes');

            throw SignatureException::locked();
        }

        $request->mergeProps(['otp_sends' => $sends])->save();

        $request->otps()->whereNull('consumed_at')->update(['consumed_at' => now()]);

        $code = static::generateCode();
        $channel = OiLaravelSign::otpChannel($request->otp_channel);

        /** @var SignatureOtp $otp */
        $otp = $request->otps()->create([
            'code_hash' => SignatureOtp::hashCode($code),
            'channel' => $request->otp_channel,
            'expires_at' => now()->addMinutes((int) config('oi-laravel-sign.otp.ttl_minutes', 10)),
        ]);

        $channel->send($request, $code);

        $request->record(SignatureEventType::OtpSent, [
            'channel' => $request->otp_channel->value,
            'destination' => $channel->destination($request),
        ]);

        return $otp;
    }

    public static function ensureCanProceed(SignatureRequest $request): void
    {
        if (ExpireSignature::ifStale($request) || ! $request->isOpen()) {
            throw SignatureException::notOpen($request->status);
        }

        if ($request->isLocked()) {
            throw SignatureException::locked();
        }
    }

    protected static function generateCode(): string
    {
        $length = max(4, (int) config('oi-laravel-sign.otp.length', 6));

        return str_pad((string) random_int(0, 10 ** $length - 1), $length, '0', STR_PAD_LEFT);
    }
}
