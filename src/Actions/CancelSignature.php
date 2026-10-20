<?php

namespace OiLab\OiLaravelSign\Actions;

use Illuminate\Database\Eloquent\Model;
use OiLab\OiLaravelSign\Enums\SignatureEventType;
use OiLab\OiLaravelSign\Enums\SignatureStatus;
use OiLab\OiLaravelSign\Events\SignatureCancelled;
use OiLab\OiLaravelSign\Exceptions\SignatureException;
use OiLab\OiLaravelSign\Models\SignatureRequest;

/**
 * The issuer withdraws an open request: its link stops working.
 */
class CancelSignature
{
    public static function handle(SignatureRequest $request, ?string $reason = null, Model|int|null $by = null): SignatureRequest
    {
        if (! $request->isOpen()) {
            throw SignatureException::notOpen($request->status);
        }

        $by ??= auth()->id();

        $request->status = SignatureStatus::Cancelled;
        $request->updated_by = $by instanceof Model ? $by->getKey() : $by;
        $request->mergeProps(['cancel_reason' => $reason])->save();

        $request->record(SignatureEventType::Cancelled, array_filter(['reason' => $reason]));

        event(new SignatureCancelled($request));

        return $request;
    }
}
