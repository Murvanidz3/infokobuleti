## What and why
<!-- One or two sentences. Link the task or prompt. -->

## How to verify on dev.infokobuleti.com
1.

## Checklist
- [ ] Branch from the latest `develop`, PR targets `develop` (never `main`)
- [ ] Only what the task asked for; no new packages, no stack changes
- [ ] UI text only from lang files (ka, en, ru); existing tokens and components only
- [ ] Migrations additive and backward compatible (no dropped tables/columns or rewritten data)
- [ ] Pest tests added or updated
- [ ] `composer check` is green (Pint, PHPStan, Pest)
- [ ] Nothing deployed or run on the server; no secrets committed
