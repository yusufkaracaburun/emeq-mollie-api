---
source_url: https://docs.mollie.com/reference/webhooks
fetched: 2026-05-14
scope: Phase 5a (MOLL-04)
status: hybrid (Mollie-page is JS-rendered; signature-mechanism uit upstream SDK-source)
canonical_sdk_source: vendor/mollie/mollie-api-php/src/Webhooks/SignatureValidator.php
related_urls:
  - https://docs.mollie.com/reference/webhooks-new
  - https://docs.mollie.com/reference/webhooks-best-practices
  - https://docs.mollie.com/reference/security
---

# Mollie API — Webhooks Overview

> **NB:** Mollie's public webhooks-pages (`/reference/webhooks` en `/reference/webhooks-new`) renderen via JavaScript en geven geen tekstuele content terug bij WebFetch. De signature-mechanismedetails hieronder komen daarom uit de **upstream `mollie/mollie-api-php` v3.11 source** (`SignatureValidator.php`) — die is **authoritatief voor wat we daadwerkelijk aanroepen** via `Emeq\MollieApi\Webhooks\MollieWebhookSignature` (SDK v0.1.0-alpha.1).

## Twee webhook-soorten

Mollie heeft sinds 2025 twee parallelle webhook-systemen:

| Type | Trigger | Configuratie | Signature |
|---|---|---|---|
| **Legacy per-payment** | `webhookUrl`-veld op Payment/Subscription/etc. body | Per-call meegestuurd | Geen signature (kale POST met resource-id-body) |
| **Next-gen subscription-webhooks** | Globaal geconfigureerd in Mollie Dashboard / Webhooks-API | `POST /v2/webhooks` met subscribed events | **`X-Mollie-Signature` HMAC-SHA256** |

> Bron next-gen: https://docs.mollie.com/reference/webhooks-new

Phase 5a gebruikt **next-gen subscription-webhooks** met platform-secret (één per-platform, niet per-Connection) zoals Phase 4 D-08 vereist.

## Signature-mechanisme (next-gen)

### Header

```
X-Mollie-Signature: sha256=<hex>
```

- Header-naam: **`X-Mollie-Signature`** (case-insensitive per HTTP-spec)
- Multiple signatures: Mollie kan multiple values sturen tijdens een key-rotatie-window — alle worden afzonderlijk gevalideerd; **één match is voldoende**
- Prefix: `sha256=` voor de hex-encoded HMAC
- Algoritme: **HMAC-SHA256** over de **raw request-body**

### Pseudo-code (uit SDK)

```php
// vendor/mollie/mollie-api-php/src/Webhooks/SignatureValidator.php
public const SIGNATURE_HEADER = 'X-Mollie-Signature';
private const SIGNATURE_PREFIX = 'sha256=';

public static function createSignature(string $payload, string $secret): string
{
    return hash_hmac('sha256', $payload, $secret);
}

private function isValidSignature(string $providedSignature, string $payload): bool
{
    $expectedSignature = self::createSignature($payload, $secret);
    return hash_equals($expectedSignature, $providedSignature);
}
```

### Constant-time comparison

`hash_equals()` wordt gebruikt — verplicht voor signature-vergelijking (timing-attack-prevention).

### Legacy-webhook detection

`validatePayload()` retourneert `false` (niet `throw`) als de header **helemaal ontbreekt** → wordt als "legacy" gemerkt. Bij invalid-but-present signature wordt `InvalidSignatureException` thrown.

> In Phase 5a's webhook-controller mappen we beide condities naar 400 — een legacy-webhook (geen header) is **niet acceptabel** voor next-gen, want we hebben de signature-mechanisme expliciet aangevraagd via Mollie's Webhooks-API.

## Phase 5a-implementatie (Hub-side)

Per `05a-CONTEXT.md` D-07/D-08:

### Endpoint

```
POST /webhooks/mollie/{connection_id}
```

Geen Sanctum-auth, geen `v1`-prefix. `{connection_id}` is de Hub-PK (niet `external_id`) zodat de Hub direct de Connection vindt zonder lookup.

### Flow

1. **Signature-check** via `MollieWebhookSignature::verify($request, config('services.mollie.webhook_secret'))` (SDK helper). Failure → 400 + audit-rij in `webhook_calls`.
2. **Connection-lookup**: `Connection::find($connection_id)` waar `provider='mollie'`, `revoked_at = NULL`. Niet gevonden → **410 Gone** (Mollie stopt retries op 4xx-non-408/429 — 410 is een permanent gone-signal voor revoked Connections).
3. **Anti-spoofing**: fetch het resource via SDK met de Connection's `access_token`. De webhook-body bevat alleen `{id: "tr_..."}`. `Mollie::client()->payments->get($payload['id'])` — 401/404 → de webhook claimt iets dat niet bij deze Connection hoort → 400 + audit. Reden: platform-signed webhooks → een aanvaller die de platform-secret kent kan elke `id` naar elke connection-URL posten; deze stap controleert resource-ownership.
4. **Audit-write** naar Spatie's `webhook_calls`-tabel (inkomend).
5. **Fan-out**: dispatch `ForwardMollieWebhookToConsumer`-job (`spatie/laravel-webhook-server`) → POST naar `consumers.webhook_callback_url` (nieuwe kolom, één per Consumer). Outgoing-payload is **dezelfde body** + Hub-eigen `X-Emeq-Signature`-header (HMAC met `consumers.webhook_callback_secret`).
6. **202 Accepted** naar Mollie zodra fan-out gequeued is — niet wachten op Consumer.

## Retry-policy (uit Mollie-docs, samengevat)

> Mollie's docs zeggen: webhook-endpoint moet 2xx returnen. Bij 4xx-non-2xx of timeout retried Mollie. Exact-schedule staat in https://docs.mollie.com/reference/webhooks-best-practices — pagina is JS-only, te fetchen tijdens plan-research-pass als detail nodig is.

Wat we wel zeker weten (uit Mollie-best-practices snippet):
- 5xx-responses → retry
- Timeout-threshold: respond binnen 15 seconden
- Idempotent handler vereist (zelfde webhook kan multiple-times binnenkomen)

## Phase 5a-relevantie

- SC-3 acceptance: tampered signature → 400. Test via `MollieWebhookSignature::sign('payload', 'wrong_secret')` → POST → assert 400 + audit-rij met exception-veld + géén fan-out-job dispatched.
- SDK-helper `Emeq\MollieApi\Webhooks\MollieWebhookSignature::verify($request, $secret)` is Laravel-Request-aware en geeft bool terug — geen PSR-7-bridge in controllers nodig.
- `services.mollie.webhook_secret` env-key + config-entry toevoegen in plan.

## Out-of-scope verwijzingen

- Webhook-subscription-management endpoints (`POST /v2/webhooks`, etc.) zijn in Phase 5a buiten scope — Phase 5a focust op de **ingress-handler**. Het subscriben-en-management-pad volgt later wanneer een host-app multi-event-config nodig heeft. Voor v0.2 stelt Emeq één globale subscription in via Mollie Dashboard.
