@extends('oi-laravel-sign::layout')

@php
    $title = $signatureRequest->title();
    $route = fn (string $name, array $parameters = []) => route(\OiLab\OiLaravelSign\OiLaravelSign::routeName($name), ['token' => $token, ...$parameters]);
    $status = $signatureRequest->status;
@endphp

@section('title', __('oi-laravel-sign::sign.screen.title', ['title' => $title]))

@section('content')
    <div class="stack">
        <h1>{{ $title }}</h1>
        <p class="muted">
            {{ __('oi-laravel-sign::sign.screen.issued_by', ['issuer' => $branding->name]) }}
            @if ($signatureRequest->isOpen() && $signatureRequest->expires_at)
                · {{ __('oi-laravel-sign::sign.screen.expires', ['date' => $signatureRequest->expires_at->copy()->locale(app()->getLocale())->translatedFormat('j F Y')]) }}
            @endif
        </p>
    </div>

    @if ($status === \OiLab\OiLaravelSign\Enums\SignatureStatus::Signed)
        <section class="card done">
            <div class="icon success" aria-hidden="true">✓</div>
            <h2>{{ __('oi-laravel-sign::sign.screen.signed_title') }}</h2>
            <p>{{ __('oi-laravel-sign::sign.screen.signed_text', ['date' => $signatureRequest->signed_at?->copy()->utc()->format('d/m/Y H:i')]) }}</p>
            <a class="btn btn-primary btn-block" href="{{ $route('download') }}">{{ __('oi-laravel-sign::sign.screen.download') }}</a>
            <p class="muted">{{ __('oi-laravel-sign::sign.screen.transaction', ['id' => $signatureRequest->uuid]) }}</p>
        </section>
    @elseif (! $signatureRequest->isOpen())
        <section class="card done">
            <h2>{{ __('oi-laravel-sign::sign.screen.'.$status->value.'_title') }}</h2>
            <p>{{ __('oi-laravel-sign::sign.screen.'.$status->value.'_text') }}</p>
        </section>
    @else
        <section class="card">
            <h2>{{ __('oi-laravel-sign::sign.screen.document') }}</h2>
            <object class="viewer" data="{{ $route('document') }}" type="application/pdf" aria-label="{{ $title }}"></object>
            <a class="btn btn-secondary btn-block" href="{{ $route('document') }}" target="_blank" rel="noopener">{{ __('oi-laravel-sign::sign.screen.open_document') }}</a>

            @if ($signatureRequest->attachments() !== [])
                <div class="stack">
                    <strong>{{ __('oi-laravel-sign::sign.screen.attachments') }}</strong>
                    <ul class="files">
                        @foreach ($signatureRequest->attachments() as $index => $attachment)
                            <li><a href="{{ $route('attachment', ['index' => $index + 1]) }}" target="_blank" rel="noopener">{{ $attachment->name }}</a></li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </section>

        @if ($lockedUntil)
            <div class="alert alert-error" role="alert">{{ __('oi-laravel-sign::sign.screen.locked', ['time' => $lockedUntil->format('H:i')]) }}</div>
        @endif

        @if ($awaitingCode)
            <section class="card">
                <h2>{{ __('oi-laravel-sign::sign.screen.sign') }}</h2>
                <p class="alert alert-info">{{ __('oi-laravel-sign::sign.screen.code_sent', [
                    'length' => config('oi-laravel-sign.otp.length', 6),
                    'channel' => $signatureRequest->otp_channel->label(),
                    'destination' => $destination,
                    'minutes' => config('oi-laravel-sign.otp.ttl_minutes', 10),
                ]) }}</p>

                <form method="POST" action="{{ $route('sign') }}" class="stack">
                    @csrf
                    <label>
                        {{ __('oi-laravel-sign::sign.form.code') }}
                        <input class="code" type="text" name="code" inputmode="numeric" autocomplete="one-time-code" pattern="[0-9]*" maxlength="{{ config('oi-laravel-sign.otp.length', 6) }}" required autofocus>
                    </label>
                    @error('code') <p class="alert alert-error" role="alert">{{ $message }}</p> @enderror
                    <button class="btn btn-primary btn-block" type="submit">{{ __('oi-laravel-sign::sign.form.sign') }}</button>
                </form>

                <form method="POST" action="{{ $route('resend') }}">
                    @csrf
                    <button class="btn btn-link" type="submit">{{ __('oi-laravel-sign::sign.form.resend') }}</button>
                </form>
            </section>
        @elseif (! $lockedUntil)
            <section class="card">
                <h2>{{ __('oi-laravel-sign::sign.screen.identity') }}</h2>
                @error('code') <p class="alert alert-error" role="alert">{{ $message }}</p> @enderror

                <form method="POST" action="{{ $route('otp') }}" class="stack">
                    @csrf
                    <div class="row">
                        <label>
                            {{ __('oi-laravel-sign::sign.form.first_name') }}
                            <input type="text" name="first_name" value="{{ old('first_name', $declared?->first_name) }}" autocomplete="given-name" required maxlength="100">
                        </label>
                        <label>
                            {{ __('oi-laravel-sign::sign.form.last_name') }}
                            <input type="text" name="last_name" value="{{ old('last_name', $declared?->last_name) }}" autocomplete="family-name" required maxlength="100">
                        </label>
                    </div>
                    <label>
                        {{ __('oi-laravel-sign::sign.form.role') }}
                        <input type="text" name="role" value="{{ old('role', $declared?->role ?? $signatureRequest->signer_role) }}" placeholder="{{ __('oi-laravel-sign::sign.form.role_hint') }}" autocomplete="organization-title" maxlength="150">
                    </label>
                    <label class="check">
                        <input type="checkbox" name="consent" value="1" required @checked(old('consent'))>
                        <span>{{ __('oi-laravel-sign::sign.form.consent', ['text' => $signatureRequest->consentText()]) }}</span>
                    </label>
                    @foreach (['first_name', 'last_name', 'role', 'consent'] as $field)
                        @error($field) <p class="alert alert-error" role="alert">{{ $message }}</p> @enderror
                    @endforeach
                    <button class="btn btn-primary btn-block" type="submit">{{ __('oi-laravel-sign::sign.form.continue') }}</button>
                </form>
            </section>
        @endif

        <details class="card" @if ($errors->has('reason')) open @endif>
            <summary class="btn btn-secondary btn-block">{{ __('oi-laravel-sign::sign.form.decline') }}</summary>
            <form method="POST" action="{{ $route('decline') }}" class="stack">
                @csrf
                <p class="muted">{{ __('oi-laravel-sign::sign.screen.decline_intro') }}</p>
                <label>
                    {{ __('oi-laravel-sign::sign.form.decline_reason') }}
                    <textarea name="reason" rows="3" maxlength="2000">{{ old('reason') }}</textarea>
                </label>
                @error('reason') <p class="alert alert-error" role="alert">{{ $message }}</p> @enderror
                <button class="btn btn-secondary btn-block" type="submit">{{ __('oi-laravel-sign::sign.form.confirm_decline') }}</button>
            </form>
        </details>
    @endif
@endsection
