---
title: "Configuration"
description: "Every option of config/oi-laravel-sign.php and its environment variable"
section: configuration
order: 1
---

# Configuration

```bash
php artisan vendor:publish --tag=oi-laravel-sign-config
```

| Key | Env | Default | Notes |
|-----|-----|---------|-------|
| `user_model` | | `App\Models\User` | `created_by` / `updated_by` |
| `models.*` | | package models | Swap for subclasses |
| `route.enabled` | `OI_SIGN_ROUTES` | `true` | Public screens |
| `route.prefix` / `route.name` | | `sign` / `sign.` | |
| `route.middleware` | | `['web', 'throttle:oi-sign']` | |
| `route.gate` | | `null` | Adds a `can:` middleware |
| `route.rate_limit` | `OI_SIGN_RATE_LIMIT` | `30` | Per minute and IP |
| `disk` | `OI_SIGN_DISK` | `FILESYSTEM_DISK` | Use a private disk |
| `directory` | `OI_SIGN_DIRECTORY` | `signatures` | Files under `{directory}/{uuid}/` |
| `retention_years` | `OI_SIGN_RETENTION_YEARS` | `10` | |
| `expires_in_days` | `OI_SIGN_EXPIRES_IN_DAYS` | `30` | `null`: no expiry |
| `consent_text` | `OI_SIGN_CONSENT_TEXT` | `Bon pour accord` | Per request: `consent_text` option |
| `otp.*` | `OI_SIGN_OTP_CHANNEL`, `OI_SIGN_SMS_CHANNEL` | | See [One-time codes](./otp.md) |
| `driver` | `OI_SIGN_DRIVER` | `native` | Or a `SignatureDriver` class |
| `sealer.*` | `OI_SIGN_SEALER`, `OI_SIGN_CERTIFICATE_PATH`… | `pades` | See [Sealing](./sealing.md) |
| `notifications.*` | | package notifications | `send_signed_document` mails the signed PDF |
| `expiration.enabled` | `OI_SIGN_EXPIRATION` | `true` | Schedules `sign:expire` hourly |
