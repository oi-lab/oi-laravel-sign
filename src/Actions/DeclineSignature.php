<?php

namespace OiLab\OiLaravelSign\Actions;

use OiLab\OiLaravelSign\Enums\SignatureEventType;
use OiLab\OiLaravelSign\Enums\SignatureStatus;
use OiLab\OiLaravelSign\Events\SignatureDeclined;
use OiLab\OiLaravelSign\Exceptions\SignatureException;
use OiLab\OiLaravelSign\Models\SignatureRequest;

/**
 * The signer refuses to sign, optionally saying why.
 */
class DeclineSignature
{
    public static function handle(SignatureRequest $request, ?string $reason = null): SignatureRequest
    {
        if (ExpireSignature::ifStale($request) || ! $request->isOpen()) {
            throw SignatureException::notOpen($request->status);
        }

        $reason = filled($reason) ? trim($reason) : null;

        $request->status = SignatureStatus::Declined;
        $request->decline_reason = $reason;
        $request->save();

        $request->record(SignatureEventType::Declined, array_filter(['reason' => $reason]));

        event(new SignatureDeclined($request));

        return $request;
    }
}
