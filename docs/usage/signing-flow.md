---
title: "The Signing Flow"
description: "The public screens, the one-time code, the limits and the statuses"
section: usage
order: 4
---

# The Signing Flow

The routes live under `route.prefix` (default `sign`) and are named `sign.*`:

| Route | Name | Role |
|-------|------|------|
| `GET /sign/{token}` | `sign.show` | The screen; each opening is logged, the first one marks the request `viewed` |
| `GET /sign/{token}/document` | `sign.document` | The stored PDF |
| `GET /sign/{token}/attachments/{n}` | `sign.attachment` | An attachment |
| `POST /sign/{token}/otp` | `sign.otp` | Identity (first name, last name, role) + consent → code sent |
| `POST /sign/{token}/otp/resend` | `sign.resend` | A new code (the previous one stops working) |
| `POST /sign/{token}/sign` | `sign.sign` | The code → signed |
| `POST /sign/{token}/decline` | `sign.decline` | Refusal with an optional reason |
| `GET /sign/{token}/download` | `sign.download` | The signed PDF |

## Limits

- A code is valid `otp.ttl_minutes` (10) minutes; only the last code sent works.
- After `otp.max_attempts` (5) wrong codes, or `otp.max_sends` (5) codes sent, the request is locked for
  `otp.lockout_minutes` (15).
- The routes go through the `oi-sign` rate limiter (`route.rate_limit` per minute and IP) and the `web`
  middleware (CSRF).

## Statuses

`pending` → `viewed` → `signed` | `declined`; the issuer can `cancel`; `expired` when past its date, when
`signableVersion()` changed, when `canBeSigned()` turns false, or when the stored copy was altered.

## Your own screens

Set `OI_SIGN_ROUTES=false` and drive the actions yourself: `ViewSignature`, `SendOtp`, `SignDocument`,
`DeclineSignature` (find the request with `SignatureRequest::findByToken($token)`).
