---
title: "Usage Overview"
description: "The contract, the trait, the actions and the Data objects of the package"
section: usage
order: 1
---

# Usage Overview

| Piece | Role |
|-------|------|
| `Contracts\Signable` | Implemented by your model: title, PDF, attachments, business rule, version, branding |
| `Concerns\HasSignatureRequests` | `signatureRequests()`, `latestSignatureRequest()`, `requestSignature()`, `latestSignature()`, `isSigned()` |
| `Actions\*` | One static `handle()` per step: request, resend, view, code, sign, decline, cancel, expire, verify |
| `Data\*` | What crosses the package boundary: `SignatureRequestData`, `SignatureEventData`, `SignerData`… |
| `Events\*` | `SignatureRequested`, `SignatureViewed`, `SignatureSigned`, `SignatureDeclined`, `SignatureCancelled`, `SignatureExpired` |

The package knows nothing about quotes, invoices or organizations: everything goes through `Signable`.

- [The Signable contract](./signable.md)
- [Requesting signatures](./requesting.md)
- [The signing flow](./signing-flow.md)
- [Events](./events.md)
