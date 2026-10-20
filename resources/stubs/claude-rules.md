# OI Laravel Sign

Use the `oi-laravel-sign` package to have an external signer sign any document of this application online
(simple electronic signature, no account, no third-party provider). The signed model implements
`OiLab\OiLaravelSign\Contracts\Signable` and uses the `HasSignatureRequests` trait; requests go through the
`OiLab\OiLaravelSign\Actions\*` actions (never write `signature_requests`, `signature_events` or
`signature_otps` by hand), and the host reacts to the `Signature*` events. The signed PDF (document +
certificate page, PAdES seal) and the evidence trail are produced and checked by the package.

- IMPORTANT: Activate `oilab-laravel-sign` when making a model signable, requesting, resending, cancelling
  or verifying a signature, listening to signature events, customizing the signing screens, the one-time
  codes (mail/SMS) or the seal certificate, or exposing signature status in an API or MCP tool.
