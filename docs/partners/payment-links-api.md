---
source_url: https://docs.mollie.com/reference/v2/payment-links-api/create-payment-link
fetched: 2026-05-14
scope: Phase 5a (MOLL-03)
status: live (fetched-from upstream)
primary_endpoint: POST /v2/payment-links
related_urls:
  - https://docs.mollie.com/reference/v2/payment-links-api/get-payment-link
  - https://docs.mollie.com/reference/v2/payment-links-api/list-payment-links
  - https://docs.mollie.com/reference/v2/payment-links-api/update-payment-link
  - https://docs.mollie.com/reference/v2/payment-links-api/delete-payment-link
---

# Mollie API — Payment Links

> Authoritative source: https://docs.mollie.com/reference/payment-links-api
> Deep-fetched: `create-payment-link`.

## Create payment link

### Endpoint

```
POST https://api.mollie.com/v2/payment-links
```

### Authentication

Een van:
- API key (Bearer)
- Advanced access token met `payment-links.write` scope
- OAuth access met `payment-links.write` scope

### Body parameters

| Parameter | Type | Required | Constraints | Description |
|---|---|---|---|---|
| `description` | string | Yes | Max 255 chars | Korte description (Dashboard + bank/card-statement) |
| `amount` | object\|null | No | — | `{currency, value}`. Omit → customer voert zelf bedrag in. |
| `minimumAmount` | object\|null | No | Mutually-exclusive met `amount` | Min-bedrag (alleen als `amount` niet meegeven) |
| `redirectUrl` | string\|null | No | Valid URL | Redirect na betaling |
| `webhookUrl` | string\|null | No | Public-reachable, geen localhost | Status-update-callback |
| `lines` | object[] | No | Required voor `billie`/`in3`/`klarna`/`riverty`/`voucher` | Order-line-details. Alle lines zelfde currency. |
| `billingAddress` | object | No | Required voor `in3`/`klarna`/`billie`/`riverty` | Customer-billing-details. |
| `shippingAddress` | object | No | — | Customer-shipping-details. |
| `profileId` | string\|null | No | Required voor org-level credentials | Profile-id |
| `reusable` | boolean | No | Default `false` | `true` → link is multi-use |
| `expiresAt` | string\|null | No | ISO 8601 | Expiry-datetime. Omit → nooit |
| `allowedMethods` | string[] | No | — | Filter beschikbare methods op deze link |
| `applicationFee` | object | No | **Mollie Connect** | Fee per payment via deze link |
| `sequenceType` | string | No | `oneoff` (default) of `first` | `first` → establishes mandate na payment |
| `customerId` | string\|null | No | Required als `sequenceType=first` | Customer voor mandate-vastlegging |
| `testmode` | boolean | No | — | Forceer test/live |

### Allowed payment methods (voor `allowedMethods`)

`applepay`, `bacs`, `bancomatpay`, `bancontact`, `banktransfer`, `belfius`, `billie`, `blik`, `creditcard`, `eps`, `giftcard`, `ideal`, `in3`, `kbc`, `klarna`, `mbway`, `multibanco`, `mybank`, `paybybank`, `paypal`, `paysafecard`, `pointofsale`, `przelewy24`, `riverty`, `satispay`, `swish`, `trustly`, `twint`, `voucher`

### Response codes

| Code | Meaning |
|---|---|
| 201 | Newly created payment-link (HAL+JSON, includes `_links.paymentLink` voor sharing) |
| 404 | No entity with this ID exists |
| 422 | Validation failure (bv. missing `description`) |

## Andere Payment-Links-endpoints

| Method | Path | Doel |
|---|---|---|
| GET | `/v2/payment-links/{id}` | Get payment-link |
| GET | `/v2/payment-links` | List payment-links (paginated) |
| PATCH | `/v2/payment-links/{id}` | Update payment-link |
| DELETE | `/v2/payment-links/{id}` | Delete payment-link |

## Phase 5a-relevantie

- 5a-scope volgens ROADMAP: `create`, `get`, `list`. **Geen update/delete** in 5a.
- Form Request `CreatePaymentLinkRequest`: minimaal `description` (max 255). `amount` of `minimumAmount` mag, niet beide.
- Endpoint-pad in Hub: `POST /v1/mollie/payment-links`.
- `_links.paymentLink.href` is wat Consumer wil share met eindgebruikers (vergelijkbaar met `_links.checkout.href` op Payments).
