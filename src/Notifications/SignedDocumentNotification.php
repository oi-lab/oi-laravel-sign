<?php

namespace OiLab\OiLaravelSign\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use OiLab\OiLaravelSign\Models\SignatureRequest;
use OiLab\OiLaravelSign\Support\SignatureFiles;

/**
 * Confirms the signature to the signer, with the signed PDF attached.
 */
class SignedDocumentNotification extends Notification
{
    public function __construct(
        public readonly SignatureRequest $signatureRequest,
    ) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $request = $this->signatureRequest;

        $mail = (new MailMessage)
            ->subject(__('oi-laravel-sign::sign.mail.signed.subject', ['title' => $request->title()]))
            ->greeting(__('oi-laravel-sign::sign.mail.greeting', ['name' => $request->signer_name]))
            ->line(__('oi-laravel-sign::sign.mail.signed.intro', [
                'title' => $request->title(),
                'date' => $request->signed_at?->copy()->utc()->format('Y-m-d H:i:s').' UTC',
            ]))
            ->line(__('oi-laravel-sign::sign.mail.signed.outro', ['id' => $request->uuid]));

        $pdf = $request->signed_pdf_path === null ? null : SignatureFiles::get($request->signed_pdf_path);

        if ($pdf !== null) {
            $mail->attachData($pdf, $request->signedFileName(), ['mime' => 'application/pdf']);
        }

        return $mail;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'signature_request' => $this->signatureRequest->uuid,
        ];
    }
}
