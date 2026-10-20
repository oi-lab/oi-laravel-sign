<?php

use OiLab\OiLaravelSign\Enums\SignatureEventType;
use OiLab\OiLaravelSign\Enums\SignatureStatus;

it('tells the open statuses from the final ones', function (SignatureStatus $status, bool $open) {
    expect($status->isOpen())->toBe($open)
        ->and($status->isFinal())->toBe(! $open);
})->with([
    [SignatureStatus::Pending, true],
    [SignatureStatus::Viewed, true],
    [SignatureStatus::Signed, false],
    [SignatureStatus::Declined, false],
    [SignatureStatus::Cancelled, false],
    [SignatureStatus::Expired, false],
]);

it('labels every status and event in both languages', function (string $locale) {
    app()->setLocale($locale);

    foreach ([...SignatureStatus::cases(), ...SignatureEventType::cases()] as $case) {
        expect($case->label())->not->toStartWith('oi-laravel-sign::');
    }
})->with(['en', 'fr']);
