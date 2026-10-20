<?php

namespace OiLab\OiLaravelSign\Actions;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use OiLab\OiLaravelSign\Contracts\Signable;
use OiLab\OiLaravelSign\Data\SignableAttachmentData;
use OiLab\OiLaravelSign\Data\SignatureRequestData;
use OiLab\OiLaravelSign\Data\SignerData;
use OiLab\OiLaravelSign\Enums\OtpChannelType;
use OiLab\OiLaravelSign\Enums\SignatureEventType;
use OiLab\OiLaravelSign\Events\SignatureRequested;
use OiLab\OiLaravelSign\Exceptions\SignatureException;
use OiLab\OiLaravelSign\Models\SignatureRequest;
use OiLab\OiLaravelSign\OiLaravelSign;
use OiLab\OiLaravelSign\Support\SignatureFiles;
use Throwable;

/**
 * Creates a signature request: snapshots the document and its attachments,
 * fingerprints them, and mails the signing link to the signer.
 */
class RequestSignature
{
    /**
     * @param  Model&Signable  $signable
     * @param  array{
     *     owner?: Model|null,
     *     expires_at?: CarbonInterface|null,
     *     expires_in_days?: int|null,
     *     otp_channel?: OtpChannelType|string|null,
     *     consent_text?: string|null,
     *     message?: string|null,
     *     notify?: bool,
     *     supersede?: bool,
     *     created_by?: Model|int|null,
     *     props?: array<string, mixed>,
     * }  $options
     */
    public static function handle(Model $signable, SignerData $signer, array $options = []): SignatureRequestData
    {
        if (! $signable instanceof Signable) {
            throw new \InvalidArgumentException(get_class($signable).' does not implement '.Signable::class.'.');
        }

        if (! $signable->canBeSigned()) {
            throw SignatureException::notSignable();
        }

        $channel = static::channel($options['otp_channel'] ?? null);

        if ($channel === OtpChannelType::Sms && blank($signer->phone)) {
            throw SignatureException::missingPhone();
        }

        if ($options['supersede'] ?? true) {
            static::supersedeOpenRequests($signable);
        }

        $uuid = (string) Str::uuid();
        $token = Str::random(64);
        $pdf = $signable->signablePdf();

        try {
            $request = DB::transaction(function () use ($signable, $signer, $options, $channel, $uuid, $token, $pdf) {
                $documentPath = SignatureFiles::put(SignatureFiles::path($uuid, 'document.pdf'), $pdf);

                $model = OiLaravelSign::signatureRequestModel();
                $owner = $options['owner'] ?? null;
                $createdBy = $options['created_by'] ?? auth()->id();

                /** @var SignatureRequest $request */
                $request = new $model([
                    'uuid' => $uuid,
                    'owner_type' => $owner?->getMorphClass(),
                    'owner_id' => $owner?->getKey(),
                    'signer_name' => $signer->name,
                    'signer_email' => $signer->email,
                    'signer_phone' => $signer->phone,
                    'signer_role' => $signer->role,
                    'token_hash' => SignatureRequest::hashToken($token),
                    'document_path' => $documentPath,
                    'document_hash' => hash('sha256', $pdf),
                    'document_version' => $signable->signableVersion(),
                    'otp_channel' => $channel,
                    'expires_at' => static::expiresAt($options),
                    'created_by' => $createdBy instanceof Model ? $createdBy->getKey() : $createdBy,
                ]);

                $request->signable()->associate($signable);
                $request->props = [
                    ...($options['props'] ?? []),
                    'title' => $signable->signableTitle(),
                    'branding' => $signable->signableBranding()->toArray(),
                    'consent_text' => $options['consent_text'] ?? OiLaravelSign::consentText(),
                    'attachments' => static::storeAttachments($uuid, $signable->signableAttachments()),
                ];
                $request->save();

                return $request;
            });
        } catch (Throwable $exception) {
            SignatureFiles::deleteDirectory($uuid);

            throw $exception;
        }

        $notify = $options['notify'] ?? true;

        $request->record(SignatureEventType::Requested, [
            'channel' => $channel->value,
            'notified' => $notify,
            'document_hash' => $request->document_hash,
            'document_version' => $request->document_version,
        ]);

        event(new SignatureRequested($request));

        $url = OiLaravelSign::signUrl($token);

        if ($notify && $url !== null) {
            static::notify($request, $url, $options['message'] ?? null);
        }

        return $request->toData($url, $token);
    }

    public static function notify(SignatureRequest $request, string $url, ?string $message = null): void
    {
        $notification = OiLaravelSign::signatureRequestNotification();

        Notification::route('mail', [$request->signer_email => $request->signer_name])
            ->notify(new $notification($request, $url, $message));
    }

    /**
     * @param  Model&Signable  $signable
     */
    protected static function supersedeOpenRequests(Model $signable): void
    {
        OiLaravelSign::signatureRequestModel()::query()
            ->whereMorphedTo('signable', $signable)
            ->open()
            ->get()
            ->each(fn (SignatureRequest $request) => CancelSignature::handle($request, 'superseded'));
    }

    protected static function channel(OtpChannelType|string|null $channel): OtpChannelType
    {
        if ($channel instanceof OtpChannelType) {
            return $channel;
        }

        return $channel === null ? OiLaravelSign::defaultOtpChannel() : OtpChannelType::from($channel);
    }

    /**
     * @param  array<string, mixed>  $options
     */
    protected static function expiresAt(array $options): ?CarbonInterface
    {
        if (array_key_exists('expires_at', $options)) {
            return $options['expires_at'];
        }

        $days = $options['expires_in_days'] ?? config('oi-laravel-sign.expires_in_days');

        return $days === null ? null : now()->addDays((int) $days)->endOfDay();
    }

    /**
     * @param  list<SignableAttachmentData>  $attachments
     * @return list<array<string, mixed>>
     */
    protected static function storeAttachments(string $uuid, array $attachments): array
    {
        $stored = [];

        foreach (array_values($attachments) as $index => $attachment) {
            $name = str($attachment->name)->replaceMatches('/[^\pL\pN._-]+/u', '-')->value();

            $stored[] = [
                'name' => $attachment->name,
                'mime_type' => $attachment->mime_type,
                'size' => strlen($attachment->contents),
                'hash' => hash('sha256', $attachment->contents),
                'path' => SignatureFiles::put(SignatureFiles::path($uuid, 'attachments/'.($index + 1).'-'.$name), $attachment->contents),
            ];
        }

        return $stored;
    }
}
