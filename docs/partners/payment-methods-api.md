---
source_url: https://docs.mollie.com/reference/v2/methods-api/list-methods
fetched: 2026-05-14
scope: Phase 5a (MOLL-03)
status: live (fetched-from upstream)
primary_endpoint: GET /v2/methods
related_urls:
  - https://docs.mollie.com/reference/v2/methods-api/get-method
  - https://docs.mollie.com/reference/v2/methods-api/list-all-methods
---

# Mollie API — Payment Methods

> Authoritative source: https://docs.mollie.com/reference/payment-methods-api
> Deep-fetched: `list-methods`.

## List payment methods

### Endpoint

```
GET https://api.mollie.com/v2/methods
```

> "Retrieve all enabled payment methods. The results of this endpoint are **not** paginated."

In test-mode: alle pending+enabled methods (of populaire defaults als geen geconfigureerd). In live: alleen volledig-enabled methods.

> Note: alleen online payment-methods (geen POS).

### Authentication

Een van:
- API key
- Advanced access token met `payments.read` scope
- OAuth access met `payments.read` scope

### Query parameters

| Parameter | Type | Required | Description |
|---|---|---|---|
| `sequenceType` | string | No | `oneoff`\|`first`\|`recurring`. Filter op payment-sequence capability. |
| `locale` | string | No | Sort methods voor country-preference; translate names. |
| `amount` | object | No | `?amount[value]=100.00&amount[currency]=USD`. Filter op support voor bedrag+currency. |
| `amount.resource` | string | No | **Deprecated.** `payments` (default) of `orders`. Orders-API niet meer aanbevolen. |
| `billingCountry` | string | No | ISO 3166-1 alpha-2. Voor country-specific methods (bv. Klarna). |
| `includeWallets` | string | No | Comma-separated wallets: `applepay`, `googlepay`. Wallet-availability vereist device-specific checks. |
| `orderLineCategories` | string | No | Comma-separated: `eco`, `gift`, `meal`, `sport_culture`, `additional`, `consume`. |
| `profileId` | string | Conditional | **Required voor org-level credentials (OAuth)**; niet nodig voor single-profile API keys. |
| `include` | string | No | `issuers` — voegt iDEAL/giftcard issuer-details toe aan response. |
| `testmode` | boolean | No | Alleen voor org-level credentials. |

### Response

#### 200 — array of method-objects

Velden per method-object:
- `id`, `description`, `minimumAmount`, `maximumAmount`
- `image.size1x`, `image.size2x`, `image.svg`
- `issuers[]` (alleen bij `?include=issuers` + ideal/giftcard)
- `_links.self`

#### 400 — Bad Request

Invalid request-parameters (bv. malformed `sequenceType`).

## Andere Methods-endpoints

| Method | Path | Doel |
|---|---|---|
| GET | `/v2/methods/{id}` | Get single payment-method |
| GET | `/v2/methods/all` | List all methods (incl. niet-enabled — voor onboarding-UI) |

## Phase 5a-relevantie

- 5a-scope: list-only (geen enable/disable in 5a — dat is Profiles-API uit `MOLL-CONNECT-RES` backlog).
- Geen Form Request nodig — alle parameters zijn optioneel query-strings; Hub valideert minimaal de `sequenceType`/`include`-enum-waardes.
- Endpoint is `payments.read`-only — gebruik PAT-ability `mollie:read` voor de gating.
