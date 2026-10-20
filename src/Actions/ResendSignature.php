<?php

namespace OiLab\OiLaravelSign\Actions;

use Illuminate\Support\Str;
use OiLab\OiLaravelSign\Data\SignatureRequestData;
use OiLab\OiLaravelSign\Enums\SignatureEventType;
use OiLab\OiLaravelSign\Exceptions\SignatureException;
use OiLab\OiLaravelSign\Models\SignatureRequest;
use OiLab\OiLaravelSign\OiLaravelSign;

/**
 * Issues a new signing link for an open request (the previous link stops
 * working) and mails it again unless `notify` is false.
 */
class ResendSignature
{
    public static function handle(SignatureRequest $request, bool $notify = true, ?string $message = null): SignatureRequestData
    {
        if (ExpireSignature::ifStale($request) || ! $request->isOpen()) {
            throw SignatureException::notOpen($request->status);
        }

        $token = Str::random(64);

        $request->token_hash = SignatureRequest::hashToken($token);
        $request->save();

        $request->record(SignatureEventType::Resent, ['notified' => $notify]);

        $url = OiLaravelSign::signUrl($token);

        if ($notify && $url !== null) {
            RequestSignature::notify($request, $url, $message);
        }

        return $request->toData($url, $token);
    }
}
