<?php

use Illuminate\Database\Eloquent\JsonEncodingException;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use OiLab\OiLaravelSign\Actions\ResendSignature;
use OiLab\OiLaravelSign\Enums\OtpChannelType;
use OiLab\OiLaravelSign\Enums\SignatureEventType;
use OiLab\OiLaravelSign\Enums\SignatureStatus;
use OiLab\OiLaravelSign\Events\SignatureCancelled;
use OiLab\OiLaravelSign\Events\SignatureRequested;
use OiLab\OiLaravelSign\Exceptions\SignatureException;
use OiLab\OiLaravelSign\Models\SignatureRequest;
use OiLab\OiLaravelSign\Notifications\SignatureRequestNotification;
use OiLab\OiLaravelSign\Tests\Fixtures\Document;
use OiLab\OiLaravelSign\Tests\Fixtures\User;

it('creates a request with a snapshot and a fingerprint of the document', function () {
    Event::fake([SignatureRequested::class]);

    [$data, $request, $document] = signatureRequest();

    expect($data->status)->toBe(SignatureStatus::Pending)
        ->and($data->title)->toBe('Quote Q-2026-042')
        ->and($data->signer->email)->toBe('jeanne@example.com')
        ->and($data->document_version)->toBe('1')
        ->and($data->otp_channel)->toBe(OtpChannelType::Mail)
        ->and($data->token)->toHaveLength(64)
        ->and($data->sign_url)->toBe(route('sign.show', $data->token))
        ->and($data->expires_at->isSameDay(now()->addDays(30)))->toBeTrue();

    $stored = Storage::disk('sign')->get($request->document_path);

    expect($request->signable->is($document))->toBeTrue()
        ->and($request->token_hash)->toBe(hash('sha256', $data->token))
        ->and($request->document_hash)->toBe(hash('sha256', $stored))
        ->and($request->branding()->name)->toBe('Acme SAS')
        ->and($request->consentText())->toBe('Bon pour accord')
        ->and($request->attachments())->toHaveCount(1)
        ->and($request->attachments()[0]->hash)->toBe(hash('sha256', Storage::disk('sign')->get($request->attachments()[0]->path)))
        ->and($request->events()->pluck('type')->all())->toBe([SignatureEventType::Requested]);

    Event::assertDispatched(SignatureRequested::class, fn ($event) => $event->signatureRequest->is($request));

    Notification::assertSentOnDemand(
        SignatureRequestNotification::class,
        fn (SignatureRequestNotification $notification, array $channels, AnonymousNotifiable $notifiable) => $notification->url === $data->sign_url
            && array_key_exists('jeanne@example.com', $notifiable->routes['mail']),
    );
});

it('keeps the token out of the serialized model', function () {
    [, $request] = signatureRequest();

    expect($request->toArray())->not->toHaveKey('token_hash');
});

it('attaches the owner, the creator and the options', function () {
    $owner = User::query()->create(['name' => 'Acme', 'email' => 'acme@example.com']);
    $creator = User::query()->create(['name' => 'Paul', 'email' => 'paul@example.com']);

    [, $request] = signatureRequest([
        'owner' => $owner,
        'created_by' => $creator,
        'consent_text' => 'Lu et approuvé',
        'expires_in_days' => 5,
        'props' => ['quote_number' => 'Q-42'],
    ]);

    expect($request->owner->is($owner))->toBeTrue()
        ->and($request->creator->is($creator))->toBeTrue()
        ->and($request->consentText())->toBe('Lu et approuvé')
        ->and($request->prop('quote_number'))->toBe('Q-42')
        ->and($request->expires_at->isSameDay(now()->addDays(5)))->toBeTrue();
});

it('records the authenticated user as creator', function () {
    $user = User::query()->create(['name' => 'Paul', 'email' => 'paul@example.com']);
    $this->actingAs($user);

    [, $request] = signatureRequest();

    expect($request->created_by)->toBe($user->id);
});

it('does not notify when asked not to', function () {
    [$data] = signatureRequest(['notify' => false]);

    expect($data->sign_url)->not->toBeNull();
    Notification::assertNothingSent();
});

it('gives the token but no link when the routes are off', function () {
    config()->set('oi-laravel-sign.route.enabled', false);

    [$data] = signatureRequest();

    expect($data->sign_url)->toBeNull()
        ->and($data->token)->toHaveLength(64);
    Notification::assertNothingSent();
});

it('refuses a model that cannot be signed', function () {
    signatureRequest(documentAttributes: ['status' => 'cancelled']);
})->throws(SignatureException::class, 'This document can no longer be signed.');

it('needs a phone number for an SMS code', function () {
    document()->requestSignature(signer(), ['otp_channel' => 'sms']);
})->throws(SignatureException::class);

it('cancels the open requests of the model it supersedes', function () {
    Event::fake([SignatureCancelled::class]);

    $document = document();
    $first = $document->requestSignature(signer());
    $second = $document->requestSignature(signer());

    expect(SignatureRequest::query()->find($first->id)->status)->toBe(SignatureStatus::Cancelled)
        ->and(SignatureRequest::query()->find($second->id)->status)->toBe(SignatureStatus::Pending)
        ->and($document->signatureRequests()->count())->toBe(2);

    Event::assertDispatchedTimes(SignatureCancelled::class, 1);
});

it('keeps the open requests when told not to supersede them', function () {
    $document = document();
    $document->requestSignature(signer());
    $document->requestSignature(signer(), ['supersede' => false]);

    expect($document->signatureRequests()->open()->count())->toBe(2);
});

it('removes the stored files when the request cannot be saved', function () {
    $document = document();

    expect(fn () => $document->requestSignature(signer(), ['props' => ['bad' => NAN]]))->toThrow(JsonEncodingException::class);

    expect(Storage::disk('sign')->allFiles())->toBe([]);
});

it('exposes the requests of a model through the trait', function () {
    $document = document();

    expect($document->latestSignature())->toBeNull()
        ->and($document->isSigned())->toBeFalse();

    $document->requestSignature(signer());
    $latest = $document->requestSignature(signer());

    expect($document->latestSignature()->id)->toBe($latest->id)
        ->and(Document::query()->with('latestSignatureRequest')->first()->latestSignature()->id)->toBe($latest->id);

    SignatureRequest::query()->whereKey($latest->id)->update(['status' => 'signed']);

    expect($document->isSigned())->toBeTrue();
});

it('resends a new link and retires the previous one', function () {
    [$data, $request] = signatureRequest();

    $resent = ResendSignature::handle($request);

    expect($resent->token)->not->toBe($data->token)
        ->and(SignatureRequest::findByToken($data->token))->toBeNull()
        ->and(SignatureRequest::findByToken($resent->token)->id)->toBe($request->id)
        ->and($request->events()->pluck('type')->last())->toBe(SignatureEventType::Resent);

    Notification::assertSentOnDemandTimes(SignatureRequestNotification::class, 2);
});
