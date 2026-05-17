---
source_url: https://docs.mollie.com/reference/v2/customers-api/create-customer
fetched: 2026-05-14
scope: Phase 5a (MOLL-03)
status: live (fetched-from upstream)
primary_endpoint: POST /v2/customers
related_urls:
  - https://docs.mollie.com/reference/v2/customers-api/get-customer
  - https://docs.mollie.com/reference/v2/customers-api/list-customers
  - https://docs.mollie.com/reference/v2/customers-api/update-customer
  - https://docs.mollie.com/reference/v2/customers-api/delete-customer
---

# Mollie API — Customers

> Authoritative source: https://docs.mollie.com/reference/customers-api
> Deep-fetched: `create-customer`. Andere endpoints linked in frontmatter.

## Create customer

### Endpoint

```
POST https://api.mollie.com/v2/customers
```

> "Creates a simple minimal representation of a customer. Payments, recurring mandates, and subscriptions can be linked to this customer object, which simplifies management of recurring payments."

### Authentication

Een van:
- API key
- Advanced access token met `customers.write` scope
- OAuth access met `customers.write` scope

### Body parameters

| Parameter | Type | Required | Description |
|---|---|---|---|
| `name` | string\|null | No | Full customer-name |
| `email` | string\|null | No | Customer-email. Non-ASCII domain → Punycode (RFC 3492) |
| `locale` | string\|null | No | Pre-set taal hosted-pages. Values: `en_US`, `en_GB`, `nl_NL`, `nl_BE`, `de_DE`, `de_AT`, `de_CH`, `de_LU`, `fr_FR`, `fr_BE`, `fr_LU`, `es_ES`, `ca_ES`, `pt_PT`, `it_IT`, `nb_NO`, `sv_SE`, `fi_FI`, `da_DK`, `is_IS`, `hu_HU`, `pl_PL`, `lv_LV`, `lt_LT` |
| `metadata` | mixed | No | Custom data (max ~1kB) |
| `testmode` | boolean\|null | No | Alleen voor org-level credentials |

### Response codes

| Code | Meaning |
|---|---|
| 201 | Customer created |
| 404 | `No entity with this ID exists` (alleen bij follow-up GET) |

## Andere Customers-endpoints

| Method | Path | Doel |
|---|---|---|
| GET | `/v2/customers/{id}` | Retrieve customer |
| GET | `/v2/customers` | List customers (paginated) |
| PATCH | `/v2/customers/{id}` | Update customer |
| DELETE | `/v2/customers/{id}` | Delete customer |

## Phase 5a-relevantie

- 5a-scope volgens ROADMAP: list/get/create (geen update/delete in 5a).
- Subscription-flow vereist `customerId` — pas op dat Consumer eerst Customer aanmaakt vóór Subscription.
- Form Requests: `CreateCustomerRequest` met optionele velden + `locale`-Rule::in op de toegestane lijst.
