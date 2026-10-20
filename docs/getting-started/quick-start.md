---
title: "Quick Start"
description: "Make a model signable and send it for signature in a few lines"
section: getting-started
order: 3
---

# Quick Start

```php
use OiLab\OiLaravelSign\Concerns\HasSignatureRequests;
use OiLab\OiLaravelSign\Contracts\Signable;
use OiLab\OiLaravelSign\Data\BrandingData;

class Contract extends Model implements Signable
{
    use HasSignatureRequests;

    public function signableTitle(): string { return $this->title; }
    public function signablePdf(): string { return $this->renderPdf(); }
    public function signableAttachments(): array { return []; }
    public function canBeSigned(): bool { return $this->status === 'sent'; }
    public function signableVersion(): ?string { return (string) $this->revision; }
    public function signableBranding(): BrandingData { return new BrandingData(name: config('app.name')); }
}
```

```php
use OiLab\OiLaravelSign\Data\SignerData;

$request = $contract->requestSignature(new SignerData(name: 'Jeanne Martin', email: 'jeanne@example.com'));
```

Jeanne receives an e-mail with the link. Once she signed:

```php
use OiLab\OiLaravelSign\Events\SignatureSigned;

Event::listen(SignatureSigned::class, function (SignatureSigned $event) {
    $event->signatureRequest->signable->update(['status' => 'signed']);
});
```
