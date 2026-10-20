<?php

namespace OiLab\OiLaravelSign\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use OiLab\OiLaravelSign\Models\SignatureRequest;

/**
 * Mails the signing link to the signer.
 */
class SignatureRequestNotification extends Notification
{
    public function __construct(
        public readonly SignatureRequest $signatureRequest,
        public readonly string $url,
        public readonly ?string $message = null,
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
        $issuer = $request->branding()->name;

        $mail = (new MailMessage)
            ->subject(__('oi-laravel-sign::sign.mail.request.subject', ['title' => $request->title(), 'issuer' => $issuer]))
            ->greeting(__('oi-laravel-sign::sign.mail.greeting', ['name' => $request->signer_name]))
            ->line(__('oi-laravel-sign::sign.mail.request.intro', ['title' => $request->title(), 'issuer' => $issuer]));

        if (filled($this->message)) {
            $mail->line($this->message);
        }

        $mail->action(__('oi-laravel-sign::sign.mail.request.action'), $this->url);

        if ($request->expires_at !== null) {
            $mail->line(__('oi-laravel-sign::sign.mail.request.expires', ['date' => $request->expires_at->copy()->locale(app()->getLocale())->translatedFormat('j F Y')]));
        }

        return $mail->line(__('oi-laravel-sign::sign.mail.request.outro'));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'signature_request' => $this->signatureRequest->uuid,
            'url' => $this->url,
        ];
    }
}
