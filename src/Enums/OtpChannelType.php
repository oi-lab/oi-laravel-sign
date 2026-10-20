<?php

namespace OiLab\OiLaravelSign\Enums;

/**
 * Where the one-time code of a request is sent: the signer's e-mail address
 * or phone number.
 */
enum OtpChannelType: string
{
    case Mail = 'mail';
    case Sms = 'sms';

    public function label(): string
    {
        return __('oi-laravel-sign::sign.channel.'.$this->value);
    }
}
