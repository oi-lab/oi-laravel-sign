---
title: "Events"
description: "The events dispatched at each outcome of a signature request"
section: usage
order: 5
---

# Events

| Event | When | Extra |
|-------|------|-------|
| `SignatureRequested` | A request was created | |
| `SignatureViewed` | First opening by the signer | |
| `SignatureSigned` | Signed PDF stored | `signed_pdf_path` on the request |
| `SignatureDeclined` | Declined by the signer | `decline_reason` on the request |
| `SignatureCancelled` | Cancelled by the issuer (or superseded) | `prop('cancel_reason')` |
| `SignatureExpired` | No longer signable | `$reason`: `expired`, `document_modified`, `document_altered`, `not_signable` |

All live in `OiLab\OiLaravelSign\Events` and carry `public readonly SignatureRequest $signatureRequest`.

```php
use OiLab\OiLaravelSign\Events\SignatureSigned;
use OiLab\OiLaravelSign\Support\SignatureFiles;

class MarkQuoteAsWon
{
    public function handle(SignatureSigned $event): void
    {
        $quote = $event->signatureRequest->signable;
        $pdf = SignatureFiles::get($event->signatureRequest->signed_pdf_path);

        $quote->markAsWon();
        $quote->attachSignedPdf($pdf);
    }
}
```
