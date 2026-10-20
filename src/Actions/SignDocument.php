<?php

namespace OiLab\OiLaravelSign\Actions;

use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Notification;
use OiLab\OiLaravelSign\Data\SignatureCertificateData;
use OiLab\OiLaravelSign\Data\SignatureEventData;
use OiLab\OiLaravelSign\Enums\SignatureEventType;
use OiLab\OiLaravelSign\Enums\SignatureStatus;
use OiLab\OiLaravelSign\Events\SignatureSigned;
use OiLab\OiLaravelSign\Exceptions\SignatureException;
use OiLab\OiLaravelSign\Models\SignatureEvent;
use OiLab\OiLaravelSign\Models\SignatureRequest;
use OiLab\OiLaravelSign\OiLaravelSign;
use OiLab\OiLaravelSign\Support\SignatureFiles;

/**
 * Signs a request with the code the signer received: checks the code, checks
 * the stored document is still the one fingerprinted when the request was
 * sent, appends the certificate page, seals and stores the signed PDF.
 */
class SignDocument
{
    public static function handle(SignatureRequest $request, string $code): SignatureRequest
    {
        if ($request->declaredSigner() === null) {
            throw SignatureException::identityMissing();
        }

        $otp = VerifyOtp::handle($request, $code);

        $pdf = SignatureFiles::get($request->document_path);

        if ($pdf === null || ! hash_equals($request->document_hash, hash('sha256', $pdf))) {
            ExpireSignature::handle($request, 'document_altered');

            throw SignatureException::documentModified();
        }

        $signedAt = now()->utc();
        $certificate = static::certificate($request, $signedAt);

        $signedPdf = OiLaravelSign::driver()->sign($request, $pdf, $certificate);
        $path = SignatureFiles::put(SignatureFiles::path($request->uuid, 'signed.pdf'), $signedPdf);
        $signedHash = hash('sha256', $signedPdf);

        $request->status = SignatureStatus::Signed;
        $request->signed_at = $signedAt;
        $request->signed_pdf_path = $path;
        $request->mergeProps([
            'signed_pdf_hash' => $signedHash,
            'retain_until' => $signedAt->copy()->addYears(OiLaravelSign::retentionYears())->toIso8601String(),
            'verification_method' => $certificate->verification_method,
        ])->save();

        $request->record(SignatureEventType::Signed, [
            'channel' => $otp->channel->value,
            'document_hash' => $request->document_hash,
            'signed_pdf_hash' => $signedHash,
        ]);

        event(new SignatureSigned($request));

        if (config('oi-laravel-sign.notifications.send_signed_document', true)) {
            $notification = OiLaravelSign::signedDocumentNotification();

            Notification::route('mail', [$request->signer_email => $request->signer_name])
                ->notify(new $notification($request));
        }

        return $request;
    }

    public static function certificate(SignatureRequest $request, CarbonInterface $signedAt): SignatureCertificateData
    {
        $channel = OiLaravelSign::otpChannel($request->otp_channel);
        $httpRequest = request();

        return new SignatureCertificateData(
            transaction_id: $request->uuid,
            title: $request->title(),
            issuer: $request->branding()->name,
            signer: $request->declaredSigner(),
            consent_text: (string) $request->prop('declared.consent_text', $request->consentText()),
            verification_channel: $request->otp_channel,
            verification_method: __('oi-laravel-sign::sign.certificate.method', [
                'channel' => $request->otp_channel->label(),
                'destination' => $channel->destination($request),
            ]),
            document_hash: $request->document_hash,
            document_version: $request->document_version,
            requested_at: $request->created_at->copy()->utc(),
            signed_at: $signedAt,
            ip: $httpRequest->ip(),
            user_agent: $httpRequest->userAgent(),
            attachments: $request->attachments(),
            events: $request->events()->get()
                ->map(fn (SignatureEvent $event) => SignatureEventData::fromModel($event))
                ->values()
                ->all(),
        );
    }
}
