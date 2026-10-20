---
title: "Sealing"
description: "The seal certificate, the pades and none sealers, and custom sealers"
section: configuration
order: 3
---

# Sealing

```php
'sealer' => [
    'driver' => env('OI_SIGN_SEALER', 'pades'),
    'certificate' => env('OI_SIGN_CERTIFICATE_PATH'),
    'private_key' => env('OI_SIGN_PRIVATE_KEY_PATH'),
    'password' => env('OI_SIGN_CERTIFICATE_PASSWORD', ''),
    'extra_certificates' => env('OI_SIGN_EXTRA_CERTIFICATES_PATH'),
    'reason' => env('OI_SIGN_SEAL_REASON', 'Electronic signature'),
    'location' => env('OI_SIGN_SEAL_LOCATION'),
    'contact' => env('OI_SIGN_SEAL_CONTACT'),
],
```

| Sealer | Result |
|--------|--------|
| `pades` | Original pages + certificate page, sealed with a PAdES-Basic signature (ISO 32000-1, `adbe.pkcs7.detached`, SHA-256) that Acrobat and Poppler check |
| `none` | Certificate page without a seal; integrity rests on the stored SHA-256 only — development |

The certificate is either a PEM certificate with its key (`private_key`, `password`), plus an optional
chain (`extra_certificates`), or a PKCS#12 bundle (`.p12` / `.pfx`, key and chain inside, `password`).

`php artisan sign:make-certificate` creates a self-signed pair for development; PDF readers then show
the seal as valid but its issuer as unknown. For production, buy a seal certificate (e.g. an "e-seal"
from a trust service provider).

## Custom sealer

Implement `OiLab\OiLaravelSign\Contracts\PdfSealer` (`seal()` and `verify()`) and set its class as
`sealer.driver`. It is the only place that touches the PDF libraries (FPDI + TCPDF by default).
