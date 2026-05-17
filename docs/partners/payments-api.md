---
source_url: https://docs.mollie.com/reference/v2/payments-api/create-payment
fetched: 2026-05-14
scope: Phase 5a (MOLL-03)
status: live (fetched-from upstream)
primary_endpoint: POST /v2/payments
related_urls:
  - https://docs.mollie.com/reference/v2/payments-api/get-payment
  - https://docs.mollie.com/reference/v2/payments-api/cancel-payment
  - https://docs.mollie.com/reference/v2/payments-api/list-payments
---

# Mollie API — Payments

> Authoritative source: https://docs.mollie.com/reference/payments-api
> Deep-fetched: `create-payment` (below). Other endpoints linked in frontmatter — fetch on-demand bij planning of in plan-research-pass.

## Create payment

### Endpoint

```
POST https://api.mollie.com/v2/payments
```

### Authentication

Een van:
- API key (Bearer)
- Advanced access token met `payments.write` scope
- OAuth access token met `payments.write` scope

### Query parameters

| Parameter | Type | Required | Description |
|---|---|---|---|
| `include` | string\|null | No | Allowed value: `details.qrCode` (QR-code voor iDEAL/Bancontact/banktransfer) |

### Body parameters

#### Required

| Parameter | Type | Description |
|---|---|---|
| `description` | string | Max 255 chars. Verschijnt op customer's card/bank statement. Aanbevolen: gebruik unieke identifier voor order-linkage. Wordt per-methode getruncteerd. |
| `amount` | object | `{currency:"EUR", value:"10.00"}`. Min/max per methode in Mollie help-center. |
| `redirectUrl` | string\|null | URL waarnaar customer redirect na payment. Aanbevolen: include order-id. Mag weggelaten voor recurring-payments en Apple Pay met token. |

#### Optional

| Parameter | Type | Description |
|---|---|---|
| `cancelUrl` | string\|null | Redirect bij customer-cancel. Default = `redirectUrl`. Webhook fires sowieso. |
| `webhookUrl` | string\|null | Webhook endpoint voor status-updates. Moet publiek bereikbaar (geen localhost). Kritisch voor status-tracking. |
| `lines` | object[] | Order-line details (item, prijs). Alle lines moeten zelfde currency hebben. Required voor: `billie`, `in3`, `klarna`, `riverty`, `voucher`. |
| `billingAddress` | object | Customer billing-address (email OR streetAndNumber+postalCode+city+country). Helpt fraud-detection. Required voor: `alma`, `in3`, `klarna`, `billie`, `riverty`. |
| `shippingAddress` | object | Zelfde shape als billingAddress. |
| `locale` | string\|null | ISO 15897 `xx_XX`. Pre-set taal hosted-pages. Voor banktransfer: bepaalt target-account-country. Values: `en_US`, `en_GB`, `nl_NL`, `nl_BE`, `de_DE`, `de_AT`, `de_CH`, `de_LU`, `fr_FR`, `fr_BE`, `fr_LU`, `es_ES`, `ca_ES`, `pt_PT`, `it_IT`, `nb_NO`, `sv_SE`, `fi_FI`, `da_DK`, `is_IS`, `hu_HU`, `pl_PL`, `lv_LV`, `lt_LT`. |
| `method` | string\|string[] | Payment-method skip-selection-screen. Single value of array (filter). Values: `alma`, `applepay`, `bacs`, `bancomatpay`, `bancontact`, `banktransfer`, `belfius`, `billie`, `bizum`, `blik`, `creditcard`, `directdebit`, `eps`, `giftcard`, `ideal`, `in3`, `kbc`, `klarna`, `mbway`, `mobilepay`, `multibanco`, `mybank`, `paybybank`, `paypal`, `paysafecard`, `pointofsale`, `przelewy24`, `riverty`, `satispay`, `swish`, `trustly`, `twint`, `vipps`, `voucher`. |
| `issuer` | string\|null | Bank/issuer-selection (iDEAL, KBC/CBC, giftcard, voucher). **NB:** iDEAL 2 (2025) negeert dit veld. Example: `ideal_INGBNL2A`. |
| `restrictPaymentMethodsToCountry` | string\|null | ISO 3166-1 alpha-2 (bv. `NL`). Voor digital-goods VAT-compliance. Creditcard blijft beschikbaar. |
| `metadata` | mixed | Custom data (max ~1kB). |
| `captureMode` | string\|null | `automatic` (default) of `manual`. `manual` verplicht voor `riverty`. |
| `captureDelay` | string\|null | Vertraag automatic-capture. Format: `... hours` of `... days`. Max 7 dagen. Vereist `captureMode: automatic`. |
| `applicationFee` | object\|null | **Mollie Connect:** charge fee on merchant-payments (OAuth). |
| `routing` | object[] | **Mollie Connect:** split payment tussen accounts (partner-feature). |
| `sequenceType` | string | `oneoff` (default), `first` (establishes mandate), `recurring` (charges automatically). PayPal vereist account-setup. |
| `mandateId` | string | Recurring/stored-card payment — welke customer-mandate-account te debiteren. |
| `customerId` | string\|null | Required bij `sequenceType: recurring`. Enables single-click. |
| `profileId` | string | **Required voor OAuth/Org-tokens**; omit voor API-keys. |
| `dueDate` | string | Completion-deadline `YYYY-MM-DD`. |
| `storeCredentials` | boolean | Sla card-details op als mandate. Vereist `customerId`, `cardToken`, method=`creditcard`. |
| `testmode` | boolean\|null | Forceer test/live. Omit bij mode-specific credentials; gebruik bij org-level credentials. |

### Response codes

| Code | Meaning |
|---|---|
| 201 | Payment created. Body bevat `_links.checkout.href` voor customer-redirect. |
| 422 | Request validation failure (missing description, amount-too-high, etc.) |
| 503 | Payment-method-supplier unavailable (bv. iDEAL-netwerkprobleem). |

### Response shape (201)

- Payment-id, status, amount, currency
- description, customer details
- `_links.checkout.href` (customer-redirect-URL)
- `_links.self`, `_links.dashboard`
- metadata, timestamps
- Method-specifieke velden

### Notes (van Mollie)

- "Payment creation is elemental to the Mollie API"
- Webhook is kritisch voor status-tracking — zonder webhook missen status-changes
- iDEAL 2 (2025) deprecates `issuer`-field
- Locale heeft significant impact op conversion van hosted-payment-pages

## Andere Payments-endpoints

| Method | Path | Doel |
|---|---|---|
| GET | `/v2/payments/{id}` | Get payment |
| DELETE | `/v2/payments/{id}` | Cancel payment (alleen voor cancellable methods) |
| GET | `/v2/payments` | List payments (paginated) |
| PATCH | `/v2/payments/{id}` | Update payment (metadata/redirectUrl/webhookUrl/description) |

## Phase 5a-relevantie

- SC-1 happy-path: `POST /v1/mollie/payments` met realistische payload → assert response `_links.checkout.href` + `status === 'open'`.
- SC-5 idempotency: forward Consumer-`Idempotency-Key`-header → SDK gebruikt 'm; twee identieke calls → één Mollie-payment-id.
- `webhookUrl`: Hub vult standaard `https://hub.emeq.test/webhooks/mollie/{connection_id}` in als Consumer 'm leeg laat (D-08 in 05a-CONTEXT).
- Form Request `CreatePaymentRequest`: minimaal `description` (string, max 255), `amount.currency` (3-char ISO), `amount.value` (decimal-string regex), `redirectUrl` (URL).
