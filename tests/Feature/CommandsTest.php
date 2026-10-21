<?php

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Event;
use OiLab\OiLaravelSign\Actions\SendOtp;
use OiLab\OiLaravelSign\Actions\SignDocument;
use OiLab\OiLaravelSign\Enums\SignatureStatus;
use OiLab\OiLaravelSign\Events\SignatureExpired;

it('expires the requests past their date or whose document changed', function () {
    Event::fake([SignatureExpired::class]);

    [, $late] = signatureRequest(['expires_in_days' => 1]);
    [, $modified, $document] = signatureRequest();
    [, $fresh] = signatureRequest();
    $document->update(['version' => 3]);

    $this->travel(2)->days();

    $this->artisan('sign:expire')->expectsOutputToContain('2 signature request(s) expired.')->assertSuccessful();

    expect($late->refresh()->status)->toBe(SignatureStatus::Expired)
        ->and($late->prop('expire_reason'))->toBe('expired')
        ->and($modified->refresh()->status)->toBe(SignatureStatus::Expired)
        ->and($modified->prop('expire_reason'))->toBe('document_modified')
        ->and($fresh->refresh()->status)->toBe(SignatureStatus::Pending);

    Event::assertDispatchedTimes(SignatureExpired::class, 2);
});

it('schedules the expiration hourly', function () {
    $events = collect(app(Schedule::class)->events())->filter(fn ($event) => str_contains($event->command, 'sign:expire'));

    expect($events)->toHaveCount(1)
        ->and($events->first()->expression)->toBe('0 * * * *');
});

it('verifies a signed request', function () {
    [, $request] = signatureRequest();
    SendOtp::handle($request, ['first_name' => 'Jeanne', 'last_name' => 'Martin']);
    SignDocument::handle($request->refresh(), lastCode());

    $this->artisan('sign:verify', ['request' => $request->uuid])
        ->expectsOutputToContain('The signed document is valid.')
        ->assertSuccessful();

    $file = tempnam(sys_get_temp_dir(), 'oisign');
    file_put_contents($file, 'not the signed pdf');

    $this->artisan('sign:verify', ['request' => $request->uuid, '--file' => $file])->assertFailed();

    @unlink($file);
});

it('exports the evidence as JSON', function () {
    [, $request] = signatureRequest();
    $file = sys_get_temp_dir().'/oi-sign-evidence-'.uniqid().'.json';

    $this->artisan('sign:evidence', ['request' => $request->uuid, '--output' => $file])->assertSuccessful();

    $json = json_decode(file_get_contents($file), true);
    @unlink($file);

    expect($json['request']['uuid'])->toBe($request->uuid)
        ->and($json['events'][0]['type'])->toBe('requested');

    $this->artisan('sign:evidence', ['request' => 'unknown'])->assertFailed();
});

it('creates a self-signed seal certificate', function () {
    $directory = sys_get_temp_dir().'/oi-sign-cert-'.uniqid();

    $this->artisan('sign:make-certificate', ['--path' => $directory, '--name' => 'Acme'])
        ->expectsOutputToContain('OI_SIGN_CERTIFICATE_PATH=')
        ->assertSuccessful();

    expect(openssl_x509_parse(file_get_contents($directory.'/seal-certificate.pem'))['subject']['CN'])->toBe('Acme e-signature seal');

    $this->artisan('sign:make-certificate', ['--path' => $directory])->assertFailed();
});
