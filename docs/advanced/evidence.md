---
title: "Evidence and Integrity"
description: "The evidence trail, the certificate page, verification of a signed PDF and retention"
section: advanced
order: 2
---

# Evidence and Integrity

## What is kept

- `document.pdf` and the attachments, copied when the request is created, with their SHA-256.
- `signature_events`: every step (`requested`, `resent`, `viewed`, `otp_sent`, `otp_failed`,
  `otp_verified`, `locked`, `signed`, `declined`, `cancelled`, `expired`, `downloaded`) with IP, user agent
  and props. The table is append only: updating or deleting an event throws.
- `signed.pdf`: the original pages plus the certificate page (declared identity, UTC date, verification
  method, fingerprints, transaction ID = request UUID, the trail), sealed; its SHA-256 in
  `props.signed_pdf_hash`.

Files are written once under `{directory}/{uuid}/`: writing an existing path throws.

## Verify

```php
use OiLab\OiLaravelSign\Actions\VerifySignedDocument;

$result = VerifySignedDocument::handle($signatureRequest);          // stored file
$result = VerifySignedDocument::handle($signatureRequest, $bytes);  // a file someone sent you
$result->valid; $result->intact; $result->seal_valid; $result->signer_certificate;
```

```bash
php artisan sign:verify 3f2c… --file=quote-signed.pdf
```

A single changed byte breaks both the SHA-256 and the seal; bytes appended after sealing are reported too.

## Export

```bash
php artisan sign:evidence 3f2c… --output=evidence.json
```

or `$signatureRequest->evidence()` (`SignatureEvidenceData`).

## Retention

At signing, `props.retain_until` = signed date + `retention_years`. A signed request can be soft deleted,
but `forceDelete()` throws until that date.

## Personal data

The package stores the signer's name, e-mail, phone, role, declared identity, IP and user agent. Mention
them in your privacy policy.
