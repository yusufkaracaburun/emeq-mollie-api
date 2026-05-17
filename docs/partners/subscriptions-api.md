---
source_url: https://docs.mollie.com/reference/v2/subscriptions-api/create-subscription
fetched: 2026-05-14
scope: Phase 5a (MOLL-03)
status: live (fetched-from upstream)
primary_endpoint: POST /v2/customers/{customerId}/subscriptions
related_urls:
  - https://docs.mollie.com/reference/v2/subscriptions-api/get-subscription
  - https://docs.mollie.com/reference/v2/subscriptions-api/cancel-subscription
  - https://docs.mollie.com/reference/v2/subscriptions-api/list-subscriptions
  - https://docs.mollie.com/reference/v2/subscriptions-api/list-all-subscriptions
  - https://docs.mollie.com/reference/v2/subscriptions-api/update-subscription
---

# Mollie API — Subscriptions

> Authoritative source: https://docs.mollie.com/reference/subscriptions-api
> Deep-fetched: `create-subscription`.

## Create subscription

### Endpoint

```
POST https://api.mollie.com/v2/customers/{customerId}/subscriptions
```

> "With subscriptions, you can schedule recurring payments to take place at regular intervals."

### Authentication

Een van:
- API key
- Advanced access token met `subscriptions.write` scope
- OAuth access met `subscriptions.write` scope

### Path parameters

| Parameter | Type | Required | Description |
|---|---|---|---|
| `customerId` | string | Yes | Related customer-id (subscription hangt aan Customer) |

### Body parameters

#### Required

| Parameter | Type | Description |
|---|---|---|
| `amount` | object | `{currency, value}` per individual payment. |
| `interval` | string | `[N] [unit]`. Units: `days`, `weeks`, `months`. Max: 12 months / 52 weeks / 365 days. |
| `description` | string | Verschijnt op bank-statement. **Moet uniek zijn per Customer** als die multiple active subs heeft. |

#### Optional

| Parameter | Type | Description |
|---|---|---|
| `times` | integer\|null | Totaal aantal payments vóór completion. Test-mode: auto-cancel na 10 payments. |
| `startDate` | string | `YYYY-MM-DD`. Subscription-start. |
| `method` | string\|null | `creditcard`, `directdebit`, `paypal`. Omit → any valid customer-mandate gebruikt. |
| `applicationFee` | object | **Mollie Connect:** charge fee per payment. |
| `metadata` | mixed | Custom data (max ~1kB). Forward'd naar elke generated Payment. |
| `webhookUrl` | string\|null | Callback voor payment-status-changes binnen sub. **Verifieer `subscriptionId` + status.** |
| `mandateId` | string\|null | Specifieke mandate voor deze sub. |
| `profileId` | string | **Required voor OAuth/Org-tokens.** |
| `testmode` | boolean\|null | Alleen voor org-level credentials. |

### Response codes

| Code | Meaning |
|---|---|
| 201 | Newly created subscription-object |
| 404 | No entity with this ID exists |

### Example use-cases (uit Mollie-docs)

1. **Monthly charge**: `amount[currency]="EUR"`, `amount[value]="5.00"`, `interval="2 weeks"`
2. **Limited payments**: `amount[value]="20.00"`, `interval="1 day"`, `times=5`
3. **Future start**: `amount[value]="10.00"`, `interval="1 month"`, `startDate="2018-04-30"`

## Andere Subscriptions-endpoints

| Method | Path | Doel |
|---|---|---|
| GET | `/v2/customers/{customerId}/subscriptions/{subscriptionId}` | Get subscription |
| GET | `/v2/customers/{customerId}/subscriptions` | List per customer (paginated) |
| GET | `/v2/subscriptions` | List all subscriptions (org-wide) |
| PATCH | `/v2/customers/{customerId}/subscriptions/{subscriptionId}` | Update subscription |
| DELETE | `/v2/customers/{customerId}/subscriptions/{subscriptionId}` | Cancel subscription |

## Phase 5a-relevantie

- 5a-scope volgens ROADMAP: `create`, `get`, `cancel`, `list-per-customer`. **Geen update** in 5a.
- Phase 7 (`AccountSubscription` state-machine) **wrapt** deze endpoints — 5a levert alleen pass-through; Phase 7 bouwt typed service-laag erbovenop (zie `mollie-passthrough-api.md` ADR § Consequences "Phase 7 wijkt af").
- Form Request `CreateSubscriptionRequest`: minimaal `amount.currency`, `amount.value`, `interval`-regex (`^\d+ (day|days|week|weeks|month|months)$`), `description` (max 255).
- Endpoint-pad in Hub: `POST /v1/mollie/customers/{id}/subscriptions`.
