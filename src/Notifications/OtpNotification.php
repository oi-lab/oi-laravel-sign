<?php

namespace OiLab\OiLaravelSign\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use OiLab\OiLaravelSign\Models\SignatureRequest;

/**
 * Sends the one-time code by mail or SMS (`channel` is the notification
 * channel: mail, vonage, or a custom channel reading toSms()).
 */
class OtpNotification extends Notification
{
    public function __construct(
        public readonly SignatureRequest $signatureRequest,
        public readonly string $code,
        public readonly string $channel = 'mail',
    ) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return [$this->channel];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('oi-laravel-sign::sign.mail.otp.subject', ['code' => $this->code]))
            ->greeting(__('oi-laravel-sign::sign.mail.greeting', ['name' => $this->signatureRequest->signer_name]))
            ->line(__('oi-laravel-sign::sign.mail.otp.intro', ['title' => $this->signatureRequest->title()]))
            ->line('**'.$this->code.'**')
            ->line(__('oi-laravel-sign::sign.mail.otp.validity', ['minutes' => $this->ttlMinutes()]))
            ->line(__('oi-laravel-sign::sign.mail.otp.outro'));
    }

    /**
     * The text of the SMS, for custom SMS channels.
     */
    public function toSms(object $notifiable): string
    {
        return __('oi-laravel-sign::sign.sms.otp', [
            'code' => $this->code,
            'minutes' => $this->ttlMinutes(),
            'issuer' => $this->signatureRequest->branding()->name,
        ]);
    }

    /**
     * The SMS for the Vonage channel (laravel/vonage-notification-channel).
     */
    public function toVonage(object $notifiable): mixed
    {
        $message = 'Illuminate\\Notifications\\Messages\\VonageMessage';

        return class_exists($message) ? (new $message)->content($this->toSms($notifiable)) : $this->toSms($notifiable);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'signature_request' => $this->signatureRequest->uuid,
            'text' => $this->toSms($notifiable),
        ];
    }

    protected function ttlMinutes(): int
    {
        return (int) config('oi-laravel-sign.otp.ttl_minutes', 10);
    }
}
