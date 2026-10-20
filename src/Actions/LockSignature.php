<?php

namespace OiLab\OiLaravelSign\Actions;

use OiLab\OiLaravelSign\Enums\SignatureEventType;
use OiLab\OiLaravelSign\Models\SignatureRequest;

/**
 * Blocks the code step of a request for `otp.lockout_minutes` after too many
 * wrong codes or too many codes sent. A fresh code can be asked afterwards.
 */
class LockSignature
{
    public static function handle(SignatureRequest $request, string $reason): SignatureRequest
    {
        $until = now()->addMinutes((int) config('oi-laravel-sign.otp.lockout_minutes', 15));

        $request->mergeProps([
            'locked_until' => $until->toIso8601String(),
            'otp_sends' => 0,
        ])->save();

        $request->otps()->whereNull('consumed_at')->update(['consumed_at' => now()]);

        $request->record(SignatureEventType::Locked, [
            'reason' => $reason,
            'until' => $until->utc()->toIso8601String(),
        ]);

        return $request;
    }
}
