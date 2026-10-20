---
name: oilab-laravel-sign
description: Online e-signature for any Laravel model with oi-lab/oi-laravel-sign — Signable contract, HasSignatureRequests, signature actions and events, one-time codes by mail/SMS, sealed PDF with a certificate page, evidence trail. Use when making a model signable, requesting or following signatures, or customizing the signing flow.
---

# OI Laravel Sign — AI Context

`oi-lab/oi-laravel-sign` lets an external signer sign any document of the app online, without an account
and without a third-party provider: a **simple electronic signature** (eIDAS, art. 1367 Code civil). The
signer opens a link, declares their identity, consents, confirms with a one-time code (mail or SMS); the
package appends a "Signature certificate" page to the PDF, seals it (PAdES-Basic) and keeps an evidence
trail. The package knows nothing about quotes or organizations: everything goes through `Signable`.

## Core Concepts

| Model | Table | Notes |
|-------|-------|-------|
| `OiLab\OiLaravelSign\Models\SignatureRequest` | `signature_requests` | `signable` morph, optional `owner` morph, `signer_*`, `token_hash` (SHA-256 of the link token), `status`, `document_path`/`document_hash`/`document_version`, `otp_channel`, `expires_at`, `viewed_at`, `signed_at`, `signed_pdf_path`, `decline_reason`, `created_by`/`updated_by`, `props`, soft deletes |
| `OiLab\OiLaravelSign\Models\SignatureEvent` | `signature_events` | Append-only trail: `type`, `ip`, `user_agent`, `props`, `created_at`. Update/delete throws |
| `OiLab\OiLaravelSign\Models\SignatureOtp` | `signature_otps` | `code_hash` (HMAC with app.key), `channel`, `expires_at`, `attempts`, `consumed_at` |

- `SignatureStatus`: `Pending` → `Viewed` → `Signed` | `Declined`; `Cancelled` (issuer); `Expired` (past
  date, `signableVersion()` changed, `canBeSigned()` false, stored copy altered). `isOpen()` = pending/viewed.
- `SignatureEventType`: requested, resent, viewed, otp_sent, otp_failed, otp_verified, locked, signed,
  declined, cancelled, expired, downloaded. `OtpChannelType`: `Mail`, `Sms`.
- `props` keys: `title`, `branding`, `consent_text`, `attachments` (name, mime_type, size, hash, path),
  `declared` (first_name, last_name, role, consent_text, consented_at), `otp_sends`, `locked_until`,
  `signed_pdf_hash`, `retain_until`, `expire_reason`, `cancel_reason`, plus the host's `props` option.
- Files (write-once) under `{directory}/{uuid}/`: `document.pdf`, `attachments/{n}-{name}`, `signed.pdf`.
- Every class resolves through the static resolver `OiLab\OiLaravelSign\OiLaravelSign`
  (`signatureRequestModel()`, `signatureEventModel()`, `signatureOtpModel()`, `userModel()`, `disk()`,
  `driver()`, `sealer()`, `otpChannel()`, `signUrl()`) — never hardcode `::class`.

## Public API

```php
use OiLab\OiLaravelSign\Concerns\HasSignatureRequests;
use OiLab\OiLaravelSign\Contracts\Signable;
use OiLab\OiLaravelSign\Data\BrandingData;
use OiLab\OiLaravelSign\Data\SignableAttachmentData;

class Quote extends Model implements Signable
{
    use HasSignatureRequests;

    public function signableTitle(): string { return "Devis {$this->number}"; }
    public function signablePdf(): string { return app(QuotePdf::class)->render($this); } // exact bytes, copied at request time
    public function signableAttachments(): array { return [new SignableAttachmentData(name: 'CGV.pdf', contents: $cgv)]; }
    public function canBeSigned(): bool { return $this->state === 'validated'; }
    public function signableVersion(): ?string { return hash('sha256', $this->lines->toJson()); } // content only, NOT updated_at
    public function signableBranding(): BrandingData { return new BrandingData(name: $org->name, logo_url: $org->logo_url, color: '#2563eb'); }
}
```

```php
use OiLab\OiLaravelSign\Data\SignerData;

$data = $quote->requestSignature(new SignerData(name: 'Jeanne Martin', email: 'jeanne@example.com', phone: null, role: null), [
    'owner' => $organization,        // optional morph
    'otp_channel' => 'mail',         // mail | sms (sms needs the phone)
    'expires_in_days' => 30,         // or 'expires_at' => $date / null
    'consent_text' => 'Bon pour accord',
    'message' => null,               // extra line in the request mail
    'notify' => true,                // false: the host mails $data->sign_url itself
    'supersede' => true,             // cancels the model's open requests
    'created_by' => $user,           // default auth()->id()
    'props' => [],
]);
$data->sign_url; $data->token;       // ONLY available here (token stored hashed)

$quote->signatureRequests(); $quote->latestSignatureRequest(); // relations (eager-loadable)
$quote->latestSignature(); $quote->isSigned();
```

Actions (static `handle()`), all in `OiLab\OiLaravelSign\Actions`:

```php
ResendSignature::handle($request, notify: true, message: null); // new token → SignatureRequestData
CancelSignature::handle($request, reason: 'Erreur', by: $user);
ExpireSignature::handle($request, 'expired'); ExpireSignature::ifStale($request);
ViewSignature::handle($request);                                 // public flow
SendOtp::handle($request, ['first_name' => …, 'last_name' => …, 'role' => …]); // null identity = resend
SignDocument::handle($request, $code);                           // verifies the code, seals, stores
DeclineSignature::handle($request, $reason);
VerifySignedDocument::handle($request, ?$pdfBytes);              // SealVerificationData: valid, intact, sealed, seal_valid
$request->evidence();                                            // SignatureEvidenceData (exportable)
OiLab\OiLaravelSign\Support\SignatureFiles::get($request->signed_pdf_path); // signed PDF bytes
```

Failures throw `OiLab\OiLaravelSign\Exceptions\SignatureException` (`->reason`: not_signable, not_open,
locked, identity_missing, invalid_code, code_expired, document_modified, missing_phone,
sealer_not_configured, file_exists, retained…). Messages are translated and safe to show.

Data (laravel-data, snake_case): `SignatureRequestData` (`toData()`), `SignatureEventData`, `SignerData`,
`BrandingData`, `SignatureAttachmentData`, `SignatureCertificateData`, `SignatureEvidenceData`,
`SealVerificationData`.

Events (`OiLab\OiLaravelSign\Events`, each with `$signatureRequest`): `SignatureRequested`,
`SignatureViewed` (first opening), `SignatureSigned`, `SignatureDeclined`, `SignatureCancelled`,
`SignatureExpired` (+ `$reason`: expired, document_modified, document_altered, not_signable).

Public routes (`route.prefix` `sign`, names `sign.*`): `show`, `document`, `attachment`, `otp`, `resend`,
`sign`, `decline`, `download` — all `{token}`-based, `web` + `throttle:oi-sign`.

Contracts to extend: `Signable`, `OtpChannel` (send + masked destination), `PdfSealer` (seal + verify),
`SignatureDriver` (native by default; future providers).

## Commands

```bash
php artisan sign:expire                      # scheduled hourly when expiration.enabled
php artisan sign:verify {uuid} [--file=]
php artisan sign:evidence {uuid} [--output=]
php artisan sign:make-certificate [--path=] [--name=] [--days=3650] [--force]
```

## Configuration

`config/oi-laravel-sign.php` (`--tag=oi-laravel-sign-config`; screens + translations `--tag=oi-laravel-sign-stubs`):

| Key | Env | Default |
|-----|-----|---------|
| `route.enabled` / `prefix` / `name` / `middleware` / `gate` / `rate_limit` | `OI_SIGN_ROUTES`, `OI_SIGN_RATE_LIMIT` | `true`, `sign`, `sign.`, `['web','throttle:oi-sign']`, `null`, `30` |
| `disk` / `directory` / `retention_years` | `OI_SIGN_DISK`, `OI_SIGN_DIRECTORY`, `OI_SIGN_RETENTION_YEARS` | `FILESYSTEM_DISK`, `signatures`, `10` |
| `expires_in_days` / `consent_text` | `OI_SIGN_EXPIRES_IN_DAYS`, `OI_SIGN_CONSENT_TEXT` | `30`, `Bon pour accord` |
| `otp.channel` / `length` / `ttl_minutes` / `max_attempts` / `max_sends` / `lockout_minutes` | `OI_SIGN_OTP_CHANNEL` | `mail`, 6, 10, 5, 5, 15 |
| `otp.channels.*`, `otp.sms.via` | `OI_SIGN_SMS_CHANNEL` | mail/sms classes, `vonage` |
| `driver` | `OI_SIGN_DRIVER` | `native` |
| `sealer.driver` / `certificate` / `private_key` / `password` / `extra_certificates` | `OI_SIGN_SEALER`, `OI_SIGN_CERTIFICATE_PATH`, `OI_SIGN_PRIVATE_KEY_PATH`, `OI_SIGN_CERTIFICATE_PASSWORD`, `OI_SIGN_EXTRA_CERTIFICATES_PATH` | `pades` (or `none`, or a class); PEM+key or `.p12` |
| `notifications.*` / `send_signed_document` | | package notifications, `true` |
| `expiration.enabled` | `OI_SIGN_EXPIRATION` | `true` |

## Host Integration Checklist

1. `composer require oi-lab/oi-laravel-sign`, publish the config, `php artisan migrate`.
2. Seal certificate: `sign:make-certificate` in dev; a trusted e-seal certificate in production
   (`OI_SIGN_CERTIFICATE_PATH`…). Without one, signing throws `sealer_not_configured`.
3. Use a **private** disk (`OI_SIGN_DISK`); never expose the `signatures/` directory publicly.
4. Implement `Signable` + `HasSignatureRequests` on the model. `signableVersion()` must change with the
   content only (a request on a modified document expires).
5. Request with `requestSignature()`; pass `owner` in multi-tenant apps; use `notify: false` + `sign_url`
   to put the link in the host's own e-mail.
6. Listen to `SignatureViewed` / `SignatureSigned` / `SignatureDeclined` / `SignatureExpired` to update the
   model (history, state, attach the signed PDF, notify the issuer).
7. Run the scheduler (`sign:expire` hourly). For SMS, install a notification channel (`otp.sms.via`).
8. Expose status with `toData()` (never the token); verify with `VerifySignedDocument` / `sign:verify`.
9. Mention the signer data (name, e-mail, phone, IP, user agent) in the privacy policy.

## Updating the AI Skill

This file is the source of truth (`resources/stubs/ai-skill.md`). After changing the package, update it,
run `composer sync-ai-skills`, then in host apps: `php artisan oi:skills oilab-laravel-sign --project`.
