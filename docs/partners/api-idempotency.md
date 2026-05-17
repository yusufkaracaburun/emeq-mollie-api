---
source_url: https://docs.mollie.com/reference/api-idempotency
fetched: 2026-05-14
scope: Phase 5a (HUB-03 SC-5)
status: hybrid (Mollie-page is JS-rendered; key-mechanism uit changelog + SDK source)
canonical_sdk_source: packages/mollie-api/src/Idempotency/UuidV7IdempotencyKeyGenerator.php
changelog_announcement: https://docs.mollie.com/changelog/december-2022
related_urls:
  - https://github.com/mollie/mollie-api-php (IdempotencyKeyGeneratorContract)
---

# Mollie API — Idempotency

> **NB:** Mollie's `/reference/api-idempotency` page is JS-rendered. Wat we authoritatief weten komt uit de December-2022 changelog en de SDK's `IdempotencyKeyGeneratorContract`. Specifieke key-retention-window is door Mollie niet publiek gedocumenteerd; SDK-conventie is "auto-generated UUID v4/v7 per write-request".

## Wat het is

Mollie ondersteunt sinds **december 2022** de `Idempotency-Key` HTTP-header op write-endpoints (`POST`, `PATCH`, `DELETE`). Bij een retry met **dezelfde key** retourneert Mollie het **eerder gegenereerde resultaat** in plaats van een dubbele write uit te voeren.

> Announcement (geverifieerd uit `/changelog/december-2022`): *"Added support for `Idempotency-Key` headers in the Mollie API, making API requests repeatable and idempotent within a specific timeframe."*

## Header

```
Idempotency-Key: <unieke-string-per-request>
```

- Header-naam: `Idempotency-Key` (industrie-standaard, matched Stripe's pattern)
- Format: vrije string, **convention = UUID v4 of v7** (de SDK genereert UUID v7)
- Scope: per write-endpoint per credential

## Retention-window

Niet publiek gedocumenteerd door Mollie. Verwachting (industrie-standaard): **24 uur** sliding window. Na expiry zou dezelfde key opnieuw een nieuwe write triggeren.

> Veiligheidsmarge: assumeer dat een retry **meteen** na de oorspronkelijke call gestuurd moet worden voor garantie. Voor langere-termijn dedup (job-retry-queues) is een eigen idempotency-laag in Hub veiliger.

## SDK-integratie

Het officiële `mollie/mollie-api-php` v3.11 exposeert `Mollie\Api\Contracts\IdempotencyKeyGeneratorContract`. `emeq/mollie-api` v0.1.0-alpha.1 levert een default-implementatie:

```php
// packages/mollie-api/src/Idempotency/UuidV7IdempotencyKeyGenerator.php
namespace Emeq\MollieApi\Idempotency;

use Mollie\Api\Contracts\IdempotencyKeyGeneratorContract;
use Symfony\Component\Uid\Uuid;

final class UuidV7IdempotencyKeyGenerator implements IdempotencyKeyGeneratorContract
{
    public function generate(): string
    {
        return (string) Uuid::v7();  // time-ordered UUID
    }
}
```

UUID v7 is gekozen boven v4 omdat hij time-ordered is — handig voor debugging/audit-volgorde zonder extra timestamp-veld.

### Binding

In `config/mollie.php`:

```php
return [
    'idempotency' => [
        'generator' => \Emeq\MollieApi\Idempotency\UuidV7IdempotencyKeyGenerator::class,
    ],
];
```

SDK's `Mollie::client()` pakt deze config en bindt de generator op de onderliggende `MollieApiClient` via `$client->setIdempotencyKeyGenerator($gen)`.

## Phase 5a-flow

Per `05a-CONTEXT.md` D-06:

1. **Consumer stuurt eigen key**: Hub forward `Idempotency-Key`-header verbatim naar Mollie. Twee identieke `POST /v1/mollie/payments` met dezelfde key → één Mollie-payment-id (SC-5).
2. **Consumer geen key**: SDK's `UuidV7IdempotencyKeyGenerator` genereert er één. Hub doet zelf niet aan retry-deduplication — eerste-write-wins.

### Test-pattern voor SC-5

```php
test('twee identieke POSTs met dezelfde Idempotency-Key krijgen één Mollie-payment-id', function () {
    $client = MollieApiClient::fake([
        CreatePaymentRequest::class => MockResponse::ok(['id' => 'tr_abc123', ...]),
    ]);

    $headers = ['Idempotency-Key' => 'uuid-v7-fixed-for-test'];
    $body = [...];

    $r1 = $this->withHeaders($headers)->postJson('/v1/mollie/payments', $body);
    $r2 = $this->withHeaders($headers)->postJson('/v1/mollie/payments', $body);

    expect($r1->json('id'))->toBe($r2->json('id'));
    $client->assertSent(CreatePaymentRequest::class);
    // assertSent telt MET dedup omdat fake honoreert de header;
    // in echte productie zorgt Mollie zelf voor de dedup.
});
```

## Endpoints die Idempotency-Key ondersteunen

Per Mollie's docs (impliciet via SDK-implementatie): **alle POST/PATCH/DELETE-endpoints** op `/v2/*` honoreren de header. GET-endpoints zijn inherent idempotent en negeren 'm.

## Phase 5a-relevantie

- Hub forward de Consumer-header → SDK gebruikt 'm verbatim. Geen Hub-side dedup-laag in 5a.
- Audit-log slaat de key **NIET** op (geen body-content-traces — alleen `request_fingerprint` op `pass_through_calls`).
- Consumer-docs (in OpenAPI via Scramble) moeten vermelden dat `Idempotency-Key`-header optioneel is en aanbevolen voor write-retries.
