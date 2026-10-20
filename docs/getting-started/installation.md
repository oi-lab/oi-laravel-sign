---
title: "Installation"
description: "Install oi-laravel-sign, run the migrations and set up a seal certificate"
section: getting-started
order: 2
---

# Installation

```bash
composer require oi-lab/oi-laravel-sign
php artisan vendor:publish --tag=oi-laravel-sign-config
php artisan migrate
```

The migrations create `signature_requests`, `signature_events` and `signature_otps`. Publish them with
`--tag=oi-laravel-sign-migrations` if you need to change them.

## Seal certificate

The default `pades` sealer seals every signed PDF with a server certificate. For development:

```bash
php artisan sign:make-certificate
```

Copy the printed lines into `.env`:

```dotenv
OI_SIGN_CERTIFICATE_PATH=/path/to/storage/app/oi-sign/seal-certificate.pem
OI_SIGN_PRIVATE_KEY_PATH=/path/to/storage/app/oi-sign/seal-private-key.pem
```

In production, use a seal certificate issued by a trusted authority — see [Sealing](../configuration/sealing.md).

## Scheduler

`sign:expire` is scheduled hourly as soon as the Laravel scheduler runs (`expiration.enabled`).

## AI skill

```bash
php artisan oi:skills oilab-laravel-sign --project
```
