---
title: "The Signable Contract"
description: "What a model provides to be signed: PDF, attachments, version, business rule and branding"
section: usage
order: 2
---

# The Signable Contract

| Method | Returns | Notes |
|--------|---------|-------|
| `signableTitle()` | `string` | Shown on the screens, the mails and the certificate |
| `signablePdf()` | `string` | The exact PDF bytes. Copied when a request is created: the signer reads and signs that copy |
| `signableAttachments()` | `list<SignableAttachmentData>` | Terms and conditions… copied and fingerprinted too |
| `canBeSigned()` | `bool` | Business rule: false once expired, cancelled or already signed |
| `signableVersion()` | `?string` | Changes whenever the document changes; a request made on another version expires |
| `signableBranding()` | `BrandingData` | Issuer name, logo URL and CSS color of the screens |

## Choosing the version

Use a value that changes with the **content** only — a revision number or a hash of the lines — not
`updated_at`: marking the model as "sent" would then expire its own request.

```php
public function signableVersion(): ?string
{
    return hash('sha256', $this->lines->toJson().$this->total);
}
```

## Attachments

```php
use OiLab\OiLaravelSign\Data\SignableAttachmentData;

public function signableAttachments(): array
{
    return [new SignableAttachmentData(name: 'Terms.pdf', contents: Storage::get('terms.pdf'))];
}
```
