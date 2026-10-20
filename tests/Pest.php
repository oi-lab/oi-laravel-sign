<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use OiLab\OiLaravelSign\Data\SignatureRequestData;
use OiLab\OiLaravelSign\Data\SignerData;
use OiLab\OiLaravelSign\Models\SignatureRequest;
use OiLab\OiLaravelSign\Notifications\OtpNotification;
use OiLab\OiLaravelSign\Tests\Fixtures\Document;
use OiLab\OiLaravelSign\Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class)
    ->beforeEach(function () {
        Storage::fake('sign');
        Notification::fake();
    })
    ->in(__DIR__);

function document(array $attributes = []): Document
{
    return Document::query()->create(['title' => 'Quote Q-2026-042', ...$attributes]);
}

function signer(array $attributes = []): SignerData
{
    return SignerData::from(['name' => 'Jeanne Martin', 'email' => 'jeanne@example.com', ...$attributes]);
}

/**
 * @return array{0: SignatureRequestData, 1: SignatureRequest, 2: Document}
 */
function signatureRequest(array $options = [], array $documentAttributes = []): array
{
    $document = document($documentAttributes);
    $data = $document->requestSignature(signer(), $options);

    return [$data, SignatureRequest::query()->findOrFail($data->id), $document];
}

/**
 * The last one-time code sent, read from the faked notifications.
 */
function lastCode(): string
{
    $code = null;

    Notification::assertSentOnDemand(OtpNotification::class, function (OtpNotification $notification) use (&$code) {
        $code = $notification->code;

        return true;
    });

    return $code;
}

/**
 * @return array<string, string>
 */
function identity(array $attributes = []): array
{
    return ['first_name' => 'Jeanne', 'last_name' => 'Martin', 'role' => 'Gérante', 'consent' => '1', ...$attributes];
}
