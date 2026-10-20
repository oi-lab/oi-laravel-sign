<?php

namespace OiLab\OiLaravelSign\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use OiLab\OiLaravelSign\Actions\DeclineSignature;
use OiLab\OiLaravelSign\Actions\SendOtp;
use OiLab\OiLaravelSign\Actions\SignDocument;
use OiLab\OiLaravelSign\Actions\ViewSignature;
use OiLab\OiLaravelSign\Enums\SignatureEventType;
use OiLab\OiLaravelSign\Enums\SignatureStatus;
use OiLab\OiLaravelSign\Exceptions\SignatureException;
use OiLab\OiLaravelSign\Http\Requests\DeclineRequest;
use OiLab\OiLaravelSign\Http\Requests\RequestOtpRequest;
use OiLab\OiLaravelSign\Http\Requests\SignRequest;
use OiLab\OiLaravelSign\Models\SignatureRequest;
use OiLab\OiLaravelSign\OiLaravelSign;
use OiLab\OiLaravelSign\Support\SignatureFiles;
use Symfony\Component\HttpFoundation\Response;

/**
 * The public signing screens, reached through the token of the link.
 */
class SignatureController extends Controller
{
    public function show(string $token): View
    {
        $request = ViewSignature::handle($this->resolve($token));
        $otp = $request->isOpen() ? $request->otps()->whereNull('consumed_at')->latest('id')->first() : null;

        return view('oi-laravel-sign::show', [
            'signatureRequest' => $request,
            'token' => $token,
            'branding' => $request->branding(),
            'declared' => $request->declaredSigner(),
            'awaitingCode' => $otp !== null && $otp->isUsable() && $request->declaredSigner() !== null,
            'destination' => OiLaravelSign::otpChannel($request->otp_channel)->destination($request),
            'lockedUntil' => $request->isLocked() ? $request->lockedUntil() : null,
        ]);
    }

    public function document(string $token): Response
    {
        $request = $this->resolve($token);

        if ($request->status === SignatureStatus::Cancelled) {
            abort(404);
        }

        return $this->pdf((string) SignatureFiles::get($request->document_path), str($request->title())->slug()->value().'.pdf');
    }

    public function attachment(string $token, int $index): Response
    {
        $request = $this->resolve($token);
        $attachment = $request->attachments()[$index - 1] ?? null;

        if ($attachment === null || $request->status === SignatureStatus::Cancelled) {
            abort(404);
        }

        return response((string) SignatureFiles::get($attachment->path), 200, [
            'Content-Type' => $attachment->mime_type,
            'Content-Disposition' => 'inline; filename="'.addslashes($attachment->name).'"',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function otp(RequestOtpRequest $httpRequest, string $token): RedirectResponse
    {
        $request = $this->resolve($token);

        try {
            SendOtp::handle($request, $httpRequest->safe()->only(['first_name', 'last_name', 'role']));
        } catch (SignatureException $exception) {
            return $this->back($token, $exception, 'first_name');
        }

        return redirect()->route(OiLaravelSign::routeName('show'), $token)->with('oi-sign.status', 'otp_sent');
    }

    public function resend(string $token): RedirectResponse
    {
        try {
            SendOtp::handle($this->resolve($token));
        } catch (SignatureException $exception) {
            return $this->back($token, $exception, 'code');
        }

        return redirect()->route(OiLaravelSign::routeName('show'), $token)->with('oi-sign.status', 'otp_sent');
    }

    public function sign(SignRequest $httpRequest, string $token): RedirectResponse
    {
        try {
            SignDocument::handle($this->resolve($token), (string) $httpRequest->validated('code'));
        } catch (SignatureException $exception) {
            return $this->back($token, $exception, 'code');
        }

        return redirect()->route(OiLaravelSign::routeName('show'), $token)->with('oi-sign.status', 'signed');
    }

    public function decline(DeclineRequest $httpRequest, string $token): RedirectResponse
    {
        try {
            DeclineSignature::handle($this->resolve($token), $httpRequest->validated('reason'));
        } catch (SignatureException $exception) {
            return $this->back($token, $exception, 'reason');
        }

        return redirect()->route(OiLaravelSign::routeName('show'), $token)->with('oi-sign.status', 'declined');
    }

    public function download(string $token): Response
    {
        $request = $this->resolve($token);

        if (! $request->isSigned() || $request->signed_pdf_path === null) {
            abort(404);
        }

        $request->record(SignatureEventType::Downloaded);

        return $this->pdf((string) SignatureFiles::get($request->signed_pdf_path), $request->signedFileName(), 'attachment');
    }

    protected function resolve(string $token): SignatureRequest
    {
        $request = strlen($token) >= 32 ? OiLaravelSign::signatureRequestModel()::findByToken($token) : null;

        if ($request === null) {
            abort(404);
        }

        return $request;
    }

    protected function back(string $token, SignatureException $exception, string $field): RedirectResponse
    {
        return redirect()->route(OiLaravelSign::routeName('show'), $token)
            ->withInput()
            ->withErrors([$field => $exception->getMessage()]);
    }

    protected function pdf(string $contents, string $name, string $disposition = 'inline'): Response
    {
        return response($contents, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => $disposition.'; filename="'.addslashes($name).'"',
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store',
        ]);
    }
}
