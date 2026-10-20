# Changelog

All notable changes to `oi-lab/oi-laravel-sign` will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and this project adheres
to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.0] - 2026-10-20

### Added

- `Signable` contract and `HasSignatureRequests` trait to sign any Eloquent model.
- `signature_requests`, `signature_events` (append only) and `signature_otps` tables, with
  `SignatureRequest`, `SignatureEvent` and `SignatureOtp` models resolved through `OiLaravelSign`.
- Actions: `RequestSignature`, `ResendSignature`, `ViewSignature`, `SendOtp`, `VerifyOtp`, `SignDocument`,
  `DeclineSignature`, `CancelSignature`, `ExpireSignature`, `LockSignature`, `VerifySignedDocument`.
- Public, mobile-first signing screens (English and French) behind `route.enabled`, with a hashed token,
  the `oi-sign` rate limiter and CSRF protection.
- One-time codes by e-mail or SMS (6 digits, 10 minutes, 5 attempts, temporary lock).
- Document snapshot and SHA-256 fingerprint at request time, checked again at signing; requests on a
  modified document expire.
- Signed PDF = original pages + "Signature certificate" page, sealed by the `pades` sealer (PAdES-Basic,
  PKCS#7 detached) through FPDI + TCPDF; `none` sealer for development.
- `SignatureDriver` and `PdfSealer` contracts for future providers.
- Events `SignatureRequested`, `SignatureViewed`, `SignatureSigned`, `SignatureDeclined`,
  `SignatureCancelled`, `SignatureExpired`.
- Commands `sign:expire` (scheduled hourly), `sign:verify`, `sign:evidence`, `sign:make-certificate`.
- Write-once storage and a retention guard (10 years by default).
- laravel-data DTOs: `SignatureRequestData`, `SignatureEventData`, `SignerData`, `SignatureCertificateData`,
  `SignatureEvidenceData`, `SealVerificationData`, `BrandingData`.
- PHP 8.2 – 8.4, Laravel 11 – 13; 63 Pest tests.
