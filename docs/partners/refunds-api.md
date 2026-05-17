---
source_url: https://docs.mollie.com/reference/v2/refunds-api/create-refund
fetched: 2026-05-14
scope: Phase 5a (MOLL-03)
status: live (fetched-from upstream)
primary_endpoint: POST /v2/payments/{paymentId}/refunds
related_urls:
  - https://docs.mollie.com/reference/v2/refunds-api/get-refund
  - https://docs.mollie.com/reference/v2/refunds-api/list-refunds
  - https://docs.mollie.com/reference/v2/refunds-api/list-all-refunds
  - https://docs.mollie.com/reference/v2/refunds-api/cancel-refund
---

# Mollie API — Refunds

> Authoritative source: https://docs.mollie.com/reference/refunds-api
> Deep-fetched: `create-refund`.

## Create payment refund

### Endpoint

```
POST https://api.mollie.com/v2/payments/{paymentId}/refunds
```

> "Creates a refund for a specific payment. The refunded amount is credited to your customer usually either via a bank transfer or by refunding the amount to your customer's credit card."

### Authentication

Een van:
- API key
- Advanced access token met `refunds.write` scope
- OAuth access met `refunds.write` scope

### Path parameters

| Parameter | Type | Required | Description |
|---|---|---|---|
| `paymentId` | string | Yes | Id van de related payment |

### Body parameters

| Parameter | Type | Required | Description |
|---|---|---|---|
| `description` | string | Yes | Max 255 chars. Mag aan customer worden getoond (per payment-method). |
| `amount` | object | Yes | `{currency, value}`. Mag lager zijn dan original payment-amount. |
| `metadata` | mixed | Yes | Custom data (max ~1kB). |
| `externalReference` | object | No | External-reference details. |
| `reverseRouting` | boolean\|null | No | **Marketplace.** Set `true` om routed-funds van connected merchants te reverseren bij full refunds. |
| `routingReversals` | object[] | No | **Marketplace.** Granular fund-reversals. |
| `testmode` | boolean\|null | No | Alleen voor org-level credentials. |

### Response codes

| Code | Meaning |
|---|---|
| 201 | Refund-object created (HAL+JSON). |
| 404 | No payment entity with this ID exists. |
| 409 | "Two identical refund requests were submitted on the same payment in short succession." (built-in dedup) |
| 422 | Validation failure (bv. missing amount). |

### Idempotency-note

De 409 conflict-response is built-in protection tegen duplicate-submissions binnen kort timeframe (Mollie's eigen window, niet de `Idempotency-Key`-header window). Gebruik `Idempotency-Key`-header om je eigen retries veilig te maken.

## Andere Refunds-endpoints

| Method | Path | Doel |
|---|---|---|
| GET | `/v2/payments/{paymentId}/refunds/{refundId}` | Get refund |
| GET | `/v2/payments/{paymentId}/refunds` | List refunds per payment |
| GET | `/v2/refunds` | List all refunds (org-wide) |
| DELETE | `/v2/payments/{paymentId}/refunds/{refundId}` | Cancel refund (alleen als status=queued/pending) |

## Phase 5a-relevantie

- 5a-scope volgens ROADMAP: `create`, `get`, `list-per-payment` (geen cancel-refund in 5a).
- Form Request `CreateRefundRequest`: minimaal `description` (max 255), `amount.currency`, `amount.value`.
- Endpoint-pad in Hub: `POST /v1/mollie/payments/{id}/refunds` (nested onder Payment, mirror van Mollie's URL-structuur).
