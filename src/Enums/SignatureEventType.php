<?php

namespace OiLab\OiLaravelSign\Enums;

/**
 * One step of the evidence trail of a signature request.
 */
enum SignatureEventType: string
{
    case Requested = 'requested';
    case Resent = 'resent';
    case Viewed = 'viewed';
    case OtpSent = 'otp_sent';
    case OtpFailed = 'otp_failed';
    case OtpVerified = 'otp_verified';
    case Locked = 'locked';
    case Signed = 'signed';
    case Declined = 'declined';
    case Cancelled = 'cancelled';
    case Expired = 'expired';
    case Downloaded = 'downloaded';

    public function label(): string
    {
        return __('oi-laravel-sign::sign.event.'.$this->value);
    }
}
