---
title: "Customization"
description: "Swap models, notifications, screens, translations and the signature driver"
section: advanced
order: 3
---

# Customization

## Screens, certificate page and translations

```bash
php artisan vendor:publish --tag=oi-laravel-sign-stubs
```

copies `show.blade.php`, `layout.blade.php`, `pdf/certificate.blade.php` (the TCPDF-friendly HTML of the
certificate page) and the `en` / `fr` translations.

## Models

Extend a model and register it under `models.*`; every class resolves through `OiLaravelSign`
(`signatureRequestModel()`, `signatureEventModel()`, `signatureOtpModel()`).

## Notifications

Swap `notifications.signature_request`, `notifications.otp` or `notifications.signed_document` for your
own classes (same constructor arguments). Set `notifications.send_signed_document` to `false` to mail the
signed PDF yourself.

## Signature driver

`driver` (default `native`) is a `SignatureDriver`: it turns the verified request into the signed PDF.
A provider driver (Yousign, advanced signature) can plug in here.
