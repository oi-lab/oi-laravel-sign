<?php

namespace OiLab\OiLaravelSign\Observers;

use Illuminate\Support\Carbon;
use OiLab\OiLaravelSign\Exceptions\SignatureException;
use OiLab\OiLaravelSign\Models\SignatureRequest;

/**
 * Keeps a signed request and its evidence for the retention period: it can
 * be soft deleted, not deleted for good.
 */
class SignatureRequestObserver
{
    public function forceDeleting(SignatureRequest $request): void
    {
        $retainUntil = $request->prop('retain_until');

        if ($retainUntil !== null && Carbon::parse($retainUntil)->isFuture()) {
            throw SignatureException::retained();
        }
    }
}
