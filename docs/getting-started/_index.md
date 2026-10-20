---
title: "Getting Started"
description: "What oi-laravel-sign does and how a signature goes"
section: getting-started
order: 1
---

# Getting Started

`oi-lab/oi-laravel-sign` lets an external signer sign any document of your application online, without
an account and without a third-party provider. It is a **simple electronic signature** (eIDAS, article
1367 of the French Civil Code): enough to accept a quote.

A signature goes through four steps:

1. Your application creates a request on a model implementing `Signable`; the package copies its PDF,
   fingerprints it (SHA-256) and mails the link.
2. The signer opens `/sign/{token}`, reads the document and its attachments, declares their identity and
   ticks the consent box.
3. A 6-digit code is sent by e-mail or SMS; the signer types it.
4. The package checks the fingerprint and the version, appends a certificate page, seals the PDF and
   stores it. Your listeners react to `SignatureSigned`.

- [Installation](./installation.md)
- [Quick start](./quick-start.md)
