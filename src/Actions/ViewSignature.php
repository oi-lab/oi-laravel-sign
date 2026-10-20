<?php

namespace OiLab\OiLaravelSign\Actions;

use OiLab\OiLaravelSign\Enums\SignatureEventType;
use OiLab\OiLaravelSign\Enums\SignatureStatus;
use OiLab\OiLaravelSign\Events\SignatureViewed;
use OiLab\OiLaravelSign\Models\SignatureRequest;

/**
 * The signer opened the link. Every opening is logged; the first one moves
 * the request to `viewed` and dispatches SignatureViewed.
 */
class ViewSignature
{
    public static function handle(SignatureRequest $request): SignatureRequest
    {
        if (ExpireSignature::ifStale($request) || ! $request->isOpen()) {
            return $request;
        }

        $request->record(SignatureEventType::Viewed);

        if ($request->status === SignatureStatus::Pending) {
            $request->status = SignatureStatus::Viewed;
            $request->viewed_at = now();
            $request->save();

            event(new SignatureViewed($request));
        }

        return $request;
    }
}
