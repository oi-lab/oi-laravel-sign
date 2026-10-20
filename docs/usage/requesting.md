---
title: "Requesting Signatures"
description: "Create, resend, cancel and follow signature requests"
section: usage
order: 3
---

# Requesting Signatures

```php
use OiLab\OiLaravelSign\Data\SignerData;

$data = $quote->requestSignature(new SignerData(
    name: 'Jeanne Martin',
    email: 'jeanne@example.com',
    phone: '+33612345678',
    role: 'Managing director',
), [
    'owner' => $quote->organization,   // optional morph (multi-tenant)
    'otp_channel' => 'sms',            // mail (default) | sms
    'expires_in_days' => 15,           // or 'expires_at' => $date, null for none
    'consent_text' => 'Bon pour accord',
    'message' => 'Thank you for your trust.',
    'notify' => true,                  // false: send the link yourself
    'supersede' => true,               // cancel the open requests of the model
    'created_by' => $user,             // defaults to the authenticated user
    'props' => ['quote_number' => $quote->number],
]);
```

It returns a `SignatureRequestData`. `sign_url` and `token` are only filled now: the token is stored
hashed. With `notify: false`, put `$data->sign_url` in your own e-mail.

It throws `SignatureException` when `canBeSigned()` is false, or for an SMS code without a phone number.

## Resend, cancel

```php
use OiLab\OiLaravelSign\Actions\CancelSignature;
use OiLab\OiLaravelSign\Actions\ResendSignature;

$fresh = ResendSignature::handle($signatureRequest, notify: true); // new link, old one retired
CancelSignature::handle($signatureRequest, 'Wrong price');
```

## Follow

```php
$quote->latestSignature()?->status;   // SignatureStatus enum
$quote->isSigned();
Quote::with('latestSignatureRequest')->get();
$signatureRequest->toData();
```
