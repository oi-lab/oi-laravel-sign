@php
    /** @var \OiLab\OiLaravelSign\Data\SignatureCertificateData $certificate */
    $label = fn (string $key) => __('oi-laravel-sign::sign.certificate.'.$key);
    $date = fn (?\Carbon\CarbonInterface $value) => $value?->copy()->utc()->format('Y-m-d H:i:s').' UTC';
@endphp
<h1 style="font-size:16pt;color:#111827;">{{ $label('title') }}</h1>
<p style="color:#6b7280;">{{ $label('intro') }}</p>

<table cellpadding="5" cellspacing="0" border="0" style="width:100%;">
    <tr><td style="width:35%;color:#6b7280;">{{ $label('document') }}</td><td style="width:65%;"><b>{{ $certificate->title }}</b></td></tr>
    <tr><td style="color:#6b7280;">{{ $label('issuer') }}</td><td>{{ $certificate->issuer }}</td></tr>
    @if ($certificate->document_version)
        <tr><td style="color:#6b7280;">{{ $label('version') }}</td><td>{{ $certificate->document_version }}</td></tr>
    @endif
    <tr><td style="color:#6b7280;">{{ $label('document_hash') }}</td><td style="font-family:courier;font-size:8pt;">{{ $certificate->document_hash }}</td></tr>
    <tr><td style="color:#6b7280;">{{ $label('signer') }}</td><td><b>{{ $certificate->signer->name }}</b></td></tr>
    @if ($certificate->signer->role)
        <tr><td style="color:#6b7280;">{{ $label('role') }}</td><td>{{ $certificate->signer->role }}</td></tr>
    @endif
    <tr><td style="color:#6b7280;">{{ $label('email') }}</td><td>{{ $certificate->signer->email }}</td></tr>
    <tr><td style="color:#6b7280;">{{ $label('consent') }}</td><td>« {{ $certificate->consent_text }} »</td></tr>
    <tr><td style="color:#6b7280;">{{ $label('verification') }}</td><td>{{ $certificate->verification_method }}</td></tr>
    <tr><td style="color:#6b7280;">{{ $label('requested_at') }}</td><td>{{ $date($certificate->requested_at) }}</td></tr>
    <tr><td style="color:#6b7280;">{{ $label('signed_at') }}</td><td><b>{{ $date($certificate->signed_at) }}</b></td></tr>
    <tr><td style="color:#6b7280;">{{ $label('ip') }}</td><td>{{ $certificate->ip ?? '—' }}</td></tr>
    <tr><td style="color:#6b7280;">{{ $label('user_agent') }}</td><td style="font-size:8pt;">{{ \Illuminate\Support\Str::limit((string) $certificate->user_agent, 180) ?: '—' }}</td></tr>
    <tr><td style="color:#6b7280;">{{ $label('transaction') }}</td><td style="font-family:courier;">{{ $certificate->transaction_id }}</td></tr>
</table>

@if ($certificate->attachments !== [])
    <h2 style="font-size:11pt;">{{ $label('attachments') }}</h2>
    <table cellpadding="4" cellspacing="0" border="0" style="width:100%;">
        @foreach ($certificate->attachments as $attachment)
            <tr><td style="width:35%;">{{ $attachment->name }}</td><td style="width:65%;font-family:courier;font-size:8pt;">{{ $attachment->hash }}</td></tr>
        @endforeach
    </table>
@endif

@if ($certificate->events !== [])
    <h2 style="font-size:11pt;">{{ $label('trail') }}</h2>
    <table cellpadding="4" cellspacing="0" border="0.2" style="width:100%;border-color:#e5e7eb;">
        <tr style="background-color:#f3f4f6;"><td style="width:30%;"><b>{{ $label('date') }}</b></td><td style="width:40%;"><b>{{ $label('step') }}</b></td><td style="width:30%;"><b>{{ $label('ip') }}</b></td></tr>
        @foreach ($certificate->events as $event)
            <tr><td>{{ $date($event->created_at) }}</td><td>{{ $event->label }}</td><td>{{ $event->ip ?? '—' }}</td></tr>
        @endforeach
        <tr><td>{{ $date($certificate->signed_at) }}</td><td><b>{{ \OiLab\OiLaravelSign\Enums\SignatureEventType::Signed->label() }}</b></td><td>{{ $certificate->ip ?? '—' }}</td></tr>
    </table>
@endif

<p style="color:#6b7280;font-size:8pt;">{{ $label('footer') }}</p>
