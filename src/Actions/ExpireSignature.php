<?php

namespace OiLab\OiLaravelSign\Actions;

use OiLab\OiLaravelSign\Contracts\Signable;
use OiLab\OiLaravelSign\Enums\SignatureEventType;
use OiLab\OiLaravelSign\Enums\SignatureStatus;
use OiLab\OiLaravelSign\Events\SignatureExpired;
use OiLab\OiLaravelSign\Models\SignatureRequest;

/**
 * Closes an open request that can no longer be signed.
 */
class ExpireSignature
{
    public static function handle(SignatureRequest $request, string $reason = 'expired'): SignatureRequest
    {
        if (! $request->isOpen()) {
            return $request;
        }

        $request->status = SignatureStatus::Expired;
        $request->mergeProps(['expire_reason' => $reason])->save();

        $request->record(SignatureEventType::Expired, ['reason' => $reason]);

        event(new SignatureExpired($request, $reason));

        return $request;
    }

    /**
     * Expire the request when it is past its date, when its document changed
     * since it was sent, or when its model refuses to be signed.
     *
     * @return bool Whether the request was expired
     */
    public static function ifStale(SignatureRequest $request): bool
    {
        $reason = static::staleReason($request);

        if ($reason === null) {
            return false;
        }

        static::handle($request, $reason);

        return true;
    }

    public static function staleReason(SignatureRequest $request): ?string
    {
        if (! $request->isOpen()) {
            return null;
        }

        if ($request->isPastExpiry()) {
            return 'expired';
        }

        $signable = $request->signable;

        if (! $signable instanceof Signable) {
            return 'not_signable';
        }

        if ($signable->signableVersion() !== $request->document_version) {
            return 'document_modified';
        }

        if (! $signable->canBeSigned()) {
            return 'not_signable';
        }

        return null;
    }
}
