<?php

use Illuminate\Support\Facades\Storage;
use OiLab\OiLaravelSign\Actions\SendOtp;
use OiLab\OiLaravelSign\Actions\SignDocument;
use OiLab\OiLaravelSign\Actions\VerifySignedDocument;
use OiLab\OiLaravelSign\Contracts\PdfSealer;
use OiLab\OiLaravelSign\Exceptions\SignatureException;
use OiLab\OiLaravelSign\Models\SignatureRequest;
use OiLab\OiLaravelSign\Pdf\PdfSignatureInspector;
use OiLab\OiLaravelSign\Pdf\SealCertificateGenerator;
use OiLab\OiLaravelSign\Support\SignatureFiles;
use OiLab\OiLaravelSign\Tests\Fixtures\Document;
use setasign\Fpdi\PdfParser\StreamReader;
use setasign\Fpdi\Tcpdf\Fpdi;

function signedRequest(array $options = []): SignatureRequest
{
    [, $request] = signatureRequest($options);

    SendOtp::handle($request, ['first_name' => 'Jeanne', 'last_name' => 'Martin']);

    return SignDocument::handle($request->refresh(), lastCode());
}

it('seals the signed PDF with the server certificate', function () {
    $request = signedRequest();
    $result = VerifySignedDocument::handle($request);

    expect($result->valid)->toBeTrue()
        ->and($result->intact)->toBeTrue()
        ->and($result->sealed)->toBeTrue()
        ->and($result->seal_valid)->toBeTrue()
        ->and($result->signer_certificate)->toContain('OI Sign test seal')
        ->and($result->sealed_at)->not->toBeNull();
});

it('detects a byte changed inside the sealed range', function () {
    $request = signedRequest();
    $pdf = Storage::disk('sign')->get($request->signed_pdf_path);
    $tampered = substr_replace($pdf, $pdf[200] === 'A' ? 'B' : 'A', 200, 1);

    $result = VerifySignedDocument::handle($request, $tampered);
    $seal = (new PdfSignatureInspector)->inspect($tampered);

    expect($result->valid)->toBeFalse()
        ->and($result->intact)->toBeFalse()
        ->and($seal->sealed)->toBeTrue()
        ->and($seal->seal_valid)->toBeFalse();
});

it('detects content appended after sealing', function () {
    $request = signedRequest();
    $pdf = Storage::disk('sign')->get($request->signed_pdf_path)."\n% appended\n";

    $seal = (new PdfSignatureInspector)->inspect($pdf);

    expect($seal->seal_valid)->toBeFalse()
        ->and($seal->error)->toBe('The PDF was changed after it was sealed.');
});

it('reports a PDF without a seal', function () {
    $seal = (new PdfSignatureInspector)->inspect(Document::pdf('Plain'));

    expect($seal->sealed)->toBeFalse()
        ->and($seal->valid)->toBeFalse();
});

it('keeps every page of the document before the certificate page', function () {
    $request = signedRequest();
    $pdf = Storage::disk('sign')->get($request->signed_pdf_path);
    $original = Storage::disk('sign')->get($request->document_path);
    $pages = fn (string $bytes) => (new Fpdi)->setSourceFile(StreamReader::createByString($bytes));

    expect($pages($pdf))->toBe($pages($original) + 1);
});

it('signs with a PKCS#12 bundle', function () {
    $pair = (new SealCertificateGenerator)->generate('P12 seal');
    $path = sys_get_temp_dir().'/oi-sign-test-'.uniqid().'.p12';
    openssl_pkcs12_export_to_file($pair['certificate'], $path, $pair['private_key'], 'secret');

    config()->set('oi-laravel-sign.sealer.certificate', $path);
    config()->set('oi-laravel-sign.sealer.password', 'secret');

    $result = VerifySignedDocument::handle(signedRequest());
    @unlink($path);

    expect($result->valid)->toBeTrue()
        ->and($result->signer_certificate)->toContain('P12 seal');
});

it('refuses to sign without a seal certificate', function () {
    config()->set('oi-laravel-sign.sealer.certificate', null);

    signedRequest();
})->throws(SignatureException::class, 'The PDF sealer is not configured');

it('appends the certificate page without a seal with the none sealer', function () {
    config()->set('oi-laravel-sign.sealer.driver', 'none');
    config()->set('oi-laravel-sign.sealer.certificate', null);

    $request = signedRequest();
    $result = VerifySignedDocument::handle($request);

    expect($result->valid)->toBeTrue()
        ->and($result->sealed)->toBeFalse()
        ->and(VerifySignedDocument::handle($request, 'altered')->valid)->toBeFalse();
});

it('rejects an unknown sealer', function () {
    config()->set('oi-laravel-sign.sealer.driver', 'nope');

    app(PdfSealer::class);
})->throws(InvalidArgumentException::class);

it('never overwrites a signed PDF', function () {
    $request = signedRequest();

    SignatureFiles::put($request->signed_pdf_path, 'other');
})->throws(SignatureException::class);

it('keeps a signed request for its retention period', function () {
    $request = signedRequest();

    $request->delete();
    expect($request->trashed())->toBeTrue();

    $request->forceDelete();
})->throws(SignatureException::class);

it('lets an unsigned request be deleted for good', function () {
    [, $request] = signatureRequest();

    $request->forceDelete();

    expect(SignatureRequest::withTrashed()->find($request->id))->toBeNull();
});

it('keeps the evidence trail append only', function () {
    [, $request] = signatureRequest();
    $event = $request->events()->first();

    expect(fn () => $event->update(['ip' => '1.2.3.4']))->toThrow(LogicException::class)
        ->and(fn () => $event->delete())->toThrow(LogicException::class);
});

it('exports the evidence file', function () {
    $request = signedRequest();
    $evidence = $request->evidence()->toArray();

    expect($evidence['request']['status'])->toBe('signed')
        ->and($evidence['consent_text'])->toBe('Bon pour accord')
        ->and($evidence['attachments'][0])->toHaveKeys(['name', 'hash'])
        ->and($evidence['attachments'][0])->not->toHaveKey('path')
        ->and(collect($evidence['events'])->pluck('type')->all())->toContain('otp_verified', 'signed')
        ->and($evidence['verification']['valid'])->toBeTrue();
});
