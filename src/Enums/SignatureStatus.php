<?php

namespace OiLab\OiLaravelSign\Enums;

/**
 * A request is open (`Pending`, then `Viewed` once the signer opened the
 * link) until it is signed, declined by the signer, cancelled by the issuer,
 * or expired (past its date, or its document changed).
 */
enum SignatureStatus: string
{
    case Pending = 'pending';
    case Viewed = 'viewed';
    case Signed = 'signed';
    case Declined = 'declined';
    case Cancelled = 'cancelled';
    case Expired = 'expired';

    public function isOpen(): bool
    {
        return in_array($this, [self::Pending, self::Viewed], true);
    }

    public function isFinal(): bool
    {
        return ! $this->isOpen();
    }

    public function label(): string
    {
        return __('oi-laravel-sign::sign.status.'.$this->value);
    }

    /**
     * @return list<self>
     */
    public static function open(): array
    {
        return [self::Pending, self::Viewed];
    }
}
