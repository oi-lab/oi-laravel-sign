<?php

use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use OiLab\OiLaravelSign\Actions\CancelSignature;
use OiLab\OiLaravelSign\Actions\VerifySignedDocument;
use OiLab\OiLaravelSign\Enums\SignatureEventType;
use OiLab\OiLaravelSign\Enums\SignatureStatus;
use OiLab\OiLaravelSign\Events\SignatureDeclined;
use OiLab\OiLaravelSign\Events\SignatureExpired;
use OiLab\OiLaravelSign\Events\SignatureSigned;
use OiLab\OiLaravelSign\Events\SignatureViewed;
use OiLab\OiLaravelSign\Notifications\OtpNotification;
use OiLab\OiLaravelSign\Notifications\SignedDocumentNotification;
use setasign\Fpdi\PdfParser\StreamReader;
use setasign\Fpdi\Tcpdf\Fpdi;

it('shows the document to the signer and records each opening', function () {
    Event::fake([SignatureViewed::class]);
    [$data, $request] = signatureRequest();

    $this->get($data->sign_url)
        ->assertOk()
        ->assertSee('Quote Q-2026-042')
        ->assertSee('Acme SAS')
        ->assertSee('Bon pour accord')
        ->assertSee('CGV 2026.pdf')
        ->assertSee('--accent: #0f766e', false);

    $this->get($data->sign_url)->assertOk();

    $request->refresh();

    expect($request->status)->toBe(SignatureStatus::Viewed)
        ->and($request->viewed_at)->not->toBeNull()
        ->and($request->events()->where('type', SignatureEventType::Viewed)->count())->toBe(2);

    Event::assertDispatchedTimes(SignatureViewed::class, 1);
});

it('serves the stored document and its attachments', function () {
    [$data, $request] = signatureRequest();

    $this->get(route('sign.document', $data->token))
        ->assertOk()
        ->assertHeader('Content-Type', 'application/pdf');

    expect($this->get(route('sign.document', $data->token))->getContent())
        ->toBe(Storage::disk('sign')->get($request->document_path));

    $this->get(route('sign.attachment', [$data->token, 1]))->assertOk();
    $this->get(route('sign.attachment', [$data->token, 2]))->assertNotFound();
});

it('answers 404 to an unknown token', function () {
    $this->get(route('sign.show', str_repeat('a', 64)))->assertNotFound();
    $this->get(route('sign.show', 'short'))->assertNotFound();
});

it('requires the identity and the consent before sending a code', function () {
    [$data] = signatureRequest();

    $this->post(route('sign.otp', $data->token), identity(['consent' => null, 'first_name' => '']))
        ->assertSessionHasErrors(['consent', 'first_name']);

    Notification::assertSentOnDemandTimes(OtpNotification::class, 0);
});

it('signs the document end to end', function () {
    Event::fake([SignatureSigned::class]);
    [$data, $request] = signatureRequest();

    $this->get($data->sign_url)->assertOk();

    $this->post(route('sign.otp', $data->token), identity())
        ->assertRedirect($data->sign_url);

    $this->get($data->sign_url)
        ->assertSee('j***@example.com')
        ->assertSee('one-time-code', false);

    $this->post(route('sign.sign', $data->token), ['code' => lastCode()])
        ->assertRedirect($data->sign_url)
        ->assertSessionHasNoErrors();

    $request->refresh();

    expect($request->status)->toBe(SignatureStatus::Signed)
        ->and($request->signed_at)->not->toBeNull()
        ->and($request->declaredSigner()->name)->toBe('Jeanne Martin')
        ->and($request->declaredSigner()->role)->toBe('Gérante')
        ->and($request->prop('retain_until'))->not->toBeNull()
        ->and($request->events()->pluck('type')->map->value->all())->toBe([
            'requested', 'viewed', 'otp_sent', 'viewed', 'otp_verified', 'signed',
        ]);

    $signedPdf = Storage::disk('sign')->get($request->signed_pdf_path);

    expect($request->prop('signed_pdf_hash'))->toBe(hash('sha256', $signedPdf))
        ->and((new Fpdi)->setSourceFile(StreamReader::createByString($signedPdf)))->toBe(2)
        ->and(VerifySignedDocument::handle($request)->valid)->toBeTrue();

    $this->get($data->sign_url)->assertSee('Download the signed document');

    $download = $this->get(route('sign.download', $data->token))
        ->assertOk()
        ->assertHeader('Content-Disposition', 'attachment; filename="quote-q-2026-042-signed.pdf"');

    expect($download->getContent())->toBe($signedPdf)
        ->and($request->events()->pluck('type')->last())->toBe(SignatureEventType::Downloaded);

    Event::assertDispatched(SignatureSigned::class);
    Notification::assertSentOnDemand(SignedDocumentNotification::class);
});

it('mails the signed PDF to the signer', function () {
    [$data, $request] = signatureRequest();

    $this->post(route('sign.otp', $data->token), identity());
    $this->post(route('sign.sign', $data->token), ['code' => lastCode()]);

    Notification::assertSentOnDemand(SignedDocumentNotification::class, function (SignedDocumentNotification $notification) {
        $mail = $notification->toMail(new AnonymousNotifiable);

        return $mail->rawAttachments[0]['name'] === 'quote-q-2026-042-signed.pdf';
    });
});

it('rejects a wrong code and counts the attempts left', function () {
    [$data, $request] = signatureRequest();

    $this->post(route('sign.otp', $data->token), identity());
    $code = lastCode();
    $wrong = $code === '000000' ? '111111' : '000000';

    $this->post(route('sign.sign', $data->token), ['code' => $wrong])
        ->assertSessionHasErrors(['code' => 'Wrong code, 4 attempts left.']);

    expect($request->refresh()->status)->toBe(SignatureStatus::Pending)
        ->and($request->events()->where('type', SignatureEventType::OtpFailed)->count())->toBe(1);

    $this->post(route('sign.sign', $data->token), ['code' => $code])->assertSessionHasNoErrors();

    expect($request->refresh()->status)->toBe(SignatureStatus::Signed);
});

it('locks the request after the last wrong attempt', function () {
    [$data, $request] = signatureRequest();

    $this->post(route('sign.otp', $data->token), identity());
    $wrong = lastCode() === '000000' ? '111111' : '000000';

    foreach (range(1, 5) as $attempt) {
        $this->post(route('sign.sign', $data->token), ['code' => $wrong]);
    }

    $request->refresh();

    expect($request->isLocked())->toBeTrue()
        ->and($request->events()->where('type', SignatureEventType::Locked)->exists())->toBeTrue()
        ->and($request->otps()->whereNull('consumed_at')->exists())->toBeFalse();

    $this->post(route('sign.otp', $data->token), identity())
        ->assertSessionHasErrors(['first_name' => 'Too many attempts. Please try again in a few minutes.']);

    $this->get($data->sign_url)->assertSee('You can ask for a new code after');

    $this->travel(16)->minutes();

    $this->post(route('sign.otp', $data->token), identity())->assertSessionHasNoErrors();
});

it('locks the request after too many codes sent', function () {
    [$data, $request] = signatureRequest();

    foreach (range(1, 5) as $send) {
        $this->post(route('sign.otp', $data->token), identity())->assertSessionHasNoErrors();
    }

    $this->post(route('sign.otp', $data->token), identity())->assertSessionHasErrors('first_name');

    expect($request->refresh()->isLocked())->toBeTrue();
});

it('refuses an expired code', function () {
    [$data] = signatureRequest();

    $this->post(route('sign.otp', $data->token), identity());
    $code = lastCode();

    $this->travel(11)->minutes();

    $this->post(route('sign.sign', $data->token), ['code' => $code])
        ->assertSessionHasErrors(['code' => 'This code has expired. Please ask for a new one.']);
});

it('only accepts the last code sent', function () {
    [$data, $request] = signatureRequest();

    $this->post(route('sign.otp', $data->token), identity());
    $first = lastCode();
    $this->post(route('sign.resend', $data->token))->assertSessionHasNoErrors();
    $second = lastCode();

    if ($first !== $second) {
        $this->post(route('sign.sign', $data->token), ['code' => $first])->assertSessionHasErrors('code');
    }

    $this->post(route('sign.sign', $data->token), ['code' => $second])->assertSessionHasNoErrors();

    expect($request->refresh()->isSigned())->toBeTrue();
});

it('cannot sign without the declared identity', function () {
    [$data] = signatureRequest();

    $this->post(route('sign.sign', $data->token), ['code' => '123456'])
        ->assertSessionHasErrors(['code' => 'Please fill in your identity first.']);

    $this->post(route('sign.resend', $data->token))
        ->assertSessionHasErrors(['code' => 'Please fill in your identity first.']);
});

it('lets the signer decline with a reason', function () {
    Event::fake([SignatureDeclined::class]);
    [$data, $request] = signatureRequest();

    $this->post(route('sign.decline', $data->token), ['reason' => 'Prix trop élevé'])
        ->assertRedirect($data->sign_url);

    $request->refresh();

    expect($request->status)->toBe(SignatureStatus::Declined)
        ->and($request->decline_reason)->toBe('Prix trop élevé');

    $this->get($data->sign_url)->assertSee('Signature declined');
    $this->post(route('sign.otp', $data->token), identity())->assertSessionHasErrors('first_name');

    Event::assertDispatched(SignatureDeclined::class);
});

it('expires the request when the document changed after it was sent', function () {
    Event::fake([SignatureExpired::class]);
    [$data, $request, $document] = signatureRequest();

    $this->post(route('sign.otp', $data->token), identity());
    $document->update(['version' => 2]);

    $this->post(route('sign.sign', $data->token), ['code' => lastCode()])->assertSessionHasErrors('code');

    expect($request->refresh()->status)->toBe(SignatureStatus::Expired)
        ->and($request->prop('expire_reason'))->toBe('document_modified');

    $this->get($data->sign_url)->assertSee('Request expired');

    Event::assertDispatched(SignatureExpired::class, fn (SignatureExpired $event) => $event->reason === 'document_modified');
});

it('refuses to sign a stored document that was altered', function () {
    [$data, $request] = signatureRequest();

    $this->post(route('sign.otp', $data->token), identity());
    Storage::disk('sign')->put($request->document_path, 'tampered');

    $this->post(route('sign.sign', $data->token), ['code' => lastCode()])
        ->assertSessionHasErrors(['code' => 'The document was modified after it was sent: it can no longer be signed.']);

    expect($request->refresh()->status)->toBe(SignatureStatus::Expired)
        ->and($request->signed_pdf_path)->toBeNull();
});

it('expires a request past its date when opened', function () {
    [$data, $request] = signatureRequest();

    $this->travel(31)->days();

    $this->get($data->sign_url)->assertOk()->assertSee('Request expired');

    expect($request->refresh()->status)->toBe(SignatureStatus::Expired);
});

it('closes a cancelled request', function () {
    [$data, $request] = signatureRequest();

    CancelSignature::handle($request, 'Erreur de prix');

    $this->get($data->sign_url)->assertSee('Request withdrawn');
    $this->get(route('sign.document', $data->token))->assertNotFound();
    $this->post(route('sign.decline', $data->token))->assertSessionHasErrors('reason');
});

it('serves no signed PDF before signing', function () {
    [$data] = signatureRequest();

    $this->get(route('sign.download', $data->token))->assertNotFound();
});

it('renders the screens in French', function () {
    app()->setLocale('fr');
    [$data] = signatureRequest();

    $this->get($data->sign_url)
        ->assertSee('Recevoir mon code')
        ->assertSee('J&#039;ai lu le document et je l&#039;accepte', false);
});
