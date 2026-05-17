---
purpose: AI-agent reference for Mollie integration in emeq-hub
api_title: Mollie API
base_url: https://api.mollie.com/v2
status: active — 11 references geland 2026-05-14 voor Phase 5a planning
---

# Mollie — AI integration context

Officiële Mollie API + Mollie Connect OAuth-references, samengesteld als research-precondition voor Phase 5a (Mollie SDK Resources + Webhooks + pass-through API). Voorkomt dat planning verzonnen partner-features genereert (zie `.ai/rules/global.md` "geen verzonnen partner-features").

## Index

### Resource APIs (Phase 5a in-scope)

| # | Bestand | Endpoint | Phase 5a-rol |
|---|---------|----------|--------------|
| 1 | [`payments-api.md`](payments-api.md) | `POST/GET /v2/payments` | Payments-controller (D-04) |
| 2 | [`customers-api.md`](customers-api.md) | `GET /v2/customers` | Customers-controller |
| 3 | [`payment-methods-api.md`](payment-methods-api.md) | `GET /v2/methods` | PaymentMethods-controller |
| 4 | [`refunds-api.md`](refunds-api.md) | `POST /v2/payments/{id}/refunds` | Refunds-controller |
| 5 | [`mandates-api.md`](mandates-api.md) | `GET /v2/customers/{id}/mandates` | Mandates-controller |
| 6 | [`subscriptions-api.md`](subscriptions-api.md) | `POST /v2/customers/{id}/subscriptions` | Subscriptions-controller |
| 7 | [`payment-links-api.md`](payment-links-api.md) | `POST /v2/payment-links` | PaymentLinks-controller |

### Cross-cutting

| Bestand | Onderwerp |
|---------|-----------|
| [`webhooks-overview.md`](webhooks-overview.md) | Mollie webhook-mechanisme + signature-verificatie (Phase 5a D-07/D-08) |
| [`api-idempotency.md`](api-idempotency.md) | `Idempotency-Key`-header (Phase 5a D-06, SC-5) |
| [`errors.md`](errors.md) | Mollie error-codes + HTTP-statuscodes (Phase 5a D-13 mapping-tabel) |
| [`oauth-overview.md`](oauth-overview.md) | Mollie Connect OAuth2-flow + 37 scopes (Phase 4 retroactief + Phase 5a) |

## Conventies

Elk bestand heeft frontmatter met:

- `source_url` — canonieke Mollie-doc-URL
- `fetched` — datum van import
- `scope` — welke phase / requirement het bestand bedient
- `status` — `live (fetched-from upstream)` of `hybrid (…)` als de Mollie-page JS-rendered is
- `related_urls` — extra Mollie-pagina's die de hoofdscope aanvullen

## Lopende artefacten

- `emeq/mollie-api` SDK foundation: `.planning/phases/02-emeq-mollie-api-foundation/` (DONE — `v0.1.0-alpha.1`)
- Mollie Connect OAuth-broker: `.planning/phases/04-mollie-connect-oauth-broker/` (DONE — alle 5 SC's bewezen)
- Mollie pass-through API: `.planning/phases/05a-mollie-sdk-resources-webhooks-pass-through-api/05a-CONTEXT.md` (READY voor planning)
- Architectuur-ADR: `.docs/decisions/mollie-passthrough-api.md`

## Out of scope (deze folder)

- Cashier-Mollie subscription-laag → komt in Phase 6 (use-case A)
- Mollie Connect partner-resources (Onboarding-status, Organizations, Profiles, Permissions, ClientLinks) → backlog `MOLL-CONNECT-RES` (Phase 5a D-10)
- Account-level subscription-state-machine → Phase 7 (use-case B)
