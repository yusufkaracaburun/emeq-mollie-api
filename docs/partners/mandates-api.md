---
source_url: https://docs.mollie.com/reference/v2/mandates-api/list-mandates
fetched: 2026-05-14
scope: Phase 5a (MOLL-03)
status: live (fetched-from upstream)
primary_endpoint: GET /v2/customers/{customerId}/mandates
related_urls:
  - https://docs.mollie.com/reference/v2/mandates-api/get-mandate
  - https://docs.mollie.com/reference/v2/mandates-api/revoke-mandate
  - https://docs.mollie.com/reference/v2/mandates-api/create-mandate
---

# Mollie API — Mandates

> Authoritative source: https://docs.mollie.com/reference/mandates-api
> Deep-fetched: `list-mandates`.

## List mandates

### Endpoint

```
GET https://api.mollie.com/v2/customers/{customerId}/mandates
```

> "Retrieve a list of all mandates." Paginated.

### Authentication

Een van:
- API key
- Advanced access token met `mandates.read` scope
- OAuth access met `mandates.read` scope

### Path parameters

| Parameter | Type | Required | Description |
|---|---|---|---|
| `customerId` | string | Yes | Related customer-id |

### Query parameters

| Parameter | Type | Required | Default | Description |
|---|---|---|---|---|
| `from` | string | No | — | Start result-set from item with given ID (paginatie-cursor). |
| `limit` | integer | No | 50 | Max items (1–250). |
| `sort` | string | No | `desc` | `asc`\|`desc`. |
| `scopes` | array | No | — | Filter op mandate-scope (zie hieronder). |
| `testmode` | boolean | No | — | Alleen voor org-level credentials. |

### Mandate-scopes (filter-values)

| Scope | Meaning |
|---|---|
| `customer-present` | Mandate voor payments waar customer present is (bv. one-click met saved card). |
| `customer-not-present` | Mandate voor MIT (Merchant-Initiated Transactions, bv. recurring/subscriptions). |

### Response codes

| Code | Meaning |
|---|---|
| 200 | List of mandate-objects. |
| 400 | Bad request (bv. invalid `from`-value). |
| 404 | No customer with this ID exists. |

### Mandate-object velden (relevant voor Phase 5a webhook anti-spoofing + Phase 7 state-machine)

- `id` (bv. `mdt_AsdF…`)
- `status`: `valid` / `pending` / `invalid`
- `method` (`directdebit` / `creditcard` / `paypal`)
- `details` (method-specifieke velden: bv. `consumerName`, `consumerAccount`, `consumerBic` voor SEPA)
- `mandateReference`, `signatureDate`
- `createdAt`
- `_links`

## Andere Mandates-endpoints

| Method | Path | Doel |
|---|---|---|
| GET | `/v2/customers/{customerId}/mandates/{mandateId}` | Get single mandate |
| DELETE | `/v2/customers/{customerId}/mandates/{mandateId}` | Revoke mandate (status → `invalid`) |
| POST | `/v2/customers/{customerId}/mandates` | Create mandate (alleen voor direct-debit + import-flow) |

## Phase 5a-relevantie

- 5a-scope volgens ROADMAP: `list`, `get`, `revoke`. **Geen `create`** in 5a — Mandates ontstaan via `Payment.sequenceType=first` (zie `payments-api.md`).
- Webhook-fan-out gebruikt `mandate-revoked` event in Phase 7 (`AccountSubscription` paused) — niet in 5a.
- Endpoint-pad in Hub: `GET /v1/mollie/customers/{id}/mandates`, etc.
