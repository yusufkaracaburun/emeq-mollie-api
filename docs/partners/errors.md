---
source_url: https://docs.mollie.com/reference/handling-errors
fetched: 2026-05-14
scope: Phase 5a (HUB-03 error-mapping)
status: hybrid (Mollie's `/reference/handling-errors` is JS-rendered; exception-hiërarchie uit upstream SDK-source + Emeq SDK-mapper)
canonical_sdk_source: vendor/mollie/mollie-api-php/src/Exceptions/
related_urls:
  - https://docs.mollie.com/reference/security
  - https://github.com/mollie/mollie-api-php/blob/main/src/Exceptions/ApiException.php
---

# Mollie API — Error Handling

> **NB:** Mollie's `/reference/handling-errors` page is JS-rendered en geeft geen tekst-content terug via WebFetch. Onderstaande exception-hiërarchie en HTTP-status-mapping komen uit de **`mollie/mollie-api-php` v3.11 source-code** — die is **authoritatief voor wat de SDK aan ons teruggeeft** en wat de Hub's `MollieUpstreamErrorMapper` matched.

## Mollie's HTTP-error-response shape

Mollie volgt **RFC 7807 (Problem Details for HTTP APIs)** met extra velden:

```json
{
  "status": 422,
  "title": "Unprocessable Entity",
  "detail": "The amount is required.",
  "field": "amount",
  "_links": {
    "documentation": {
      "href": "https://docs.mollie.com/...",
      "type": "text/html"
    }
  }
}
```

| Veld | Type | Beschrijving |
|---|---|---|
| `status` | integer | HTTP-status-code |
| `title` | string | Human-readable korte beschrijving (vaste set per status) |
| `detail` | string | Specifieke uitleg voor deze instance |
| `field` | string \| absent | **Alleen op 422 `ValidationException`** — welke body-field veroorzaakt de validation-error |
| `_links.documentation.href` | URL | Link naar relevante Mollie-docs |

## HTTP-status-codes en SDK exception-types

Onderstaande tabel is uitgehaald uit `vendor/mollie/mollie-api-php/src/Exceptions/`:

| HTTP-status | Mollie SDK-exception (`Mollie\Api\Exceptions\…`) | Emeq SDK remap (`Emeq\MollieApi\Exceptions\…`) | Hub-response (D-13) |
|---|---|---|---|
| 400 | `ApiException` | `MollieException` | 502 `mollie_error` |
| 401 | `UnauthorizedException` | `AuthenticationException` | **502** `mollie_auth_failed` (cloaked) |
| 401 (auth-header missing) | `MissingAuthenticationException` | `AuthenticationException` | 502 `mollie_auth_failed` |
| 401 (auth-format invalid) | `InvalidAuthenticationException` | `AuthenticationException` | 502 `mollie_auth_failed` |
| 401 (unknown client) | `UnrecognizedClientException` | `AuthenticationException` | 502 `mollie_auth_failed` |
| 403 | `ForbiddenException` | `AuthenticationException` | 502 `mollie_auth_failed` |
| 404 | `NotFoundException` | `NotFoundException` | 404 `not_found` |
| 405 | `MethodNotAllowedException` | `MollieException` | 502 `mollie_error` |
| 422 | `ValidationException` | `ValidationException` (heeft `field`-property) | 422 `validation_failed` |
| 429 | `TooManyRequestsException` | `RateLimitException` | 429 `rate_limited` + `Retry-After` |
| 503 | `ServiceUnavailableException` | `ServerException` | 502 `mollie_unavailable` |
| 5xx (other) | `ServerException` | `ServerException` | 502 `mollie_unavailable` |
| Network timeout | `RequestTimeoutException` | (caught als `FatalRequestException`) | 504 `mollie_timeout` |
| Retryable network | `RetryableNetworkRequestException` | idem | 504 `mollie_timeout` |
| Unknown | `MollieException` (catch-all) | `MollieException` | 502 `mollie_error` |

> Bovenstaande remap komt uit `packages/mollie-api/src/Exceptions/MollieExceptionMapper.php` (SDK v0.1.0-alpha.1) + Hub's geplande `App\Support\Mollie\MollieUpstreamErrorMapper` (Phase 5a, mirror van `App\Support\Snelstart\UpstreamErrorMapper`).

## Validation-error detail (`ValidationException`)

De Mollie SDK exposeert op `ValidationException`:

```php
public function getField(): ?string;  // bv. "amount" of "amount.value"
```

Phase 5a's Hub-response voor 422:

```json
{
  "error": "validation_failed",
  "message": "<exception getMessage()>",
  "upstream_status": 422,
  "field": "amount.value"
}
```

> Hub valideert ook zelf via Form Requests (D-01) — die laag stuurt **Laravel's** 422-shape met `errors`-object, niet deze Mollie-shape. De Mollie-422 lekt dus alleen door als de Form Request 'm doorlaat (bv. velden die Hub niet kent maar Mollie wel valideert).

## Rate-limit-headers (429)

Mollie stuurt op 429:

```
Retry-After: <seconds>
```

Hub forward deze header verbatim in de 429-response naar Consumer.

> SDK's `RateLimitException` exposeert dit als `->retryAfterSeconds` (zie `Emeq\MollieApi\Exceptions\RateLimitException`).

## 401/403 → 502 cloaking (Phase 5a security-rationale)

Per `.docs/decisions/upstream-error-mapping.md` (Snelstart-pad, **gespiegeld voor Mollie**):

- Een Consumer mag niet kunnen onderscheiden of zijn eigen PAT faalt (Hub-zijdige 401 van Sanctum, gegenereerd vóór de pass-through-controller) versus de opgeslagen `access_token` voor zijn Account faalt (Mollie-zijdige 401, gegenereerd in de catch-block rondom de SDK-call).
- 401/403 worden dus naar **502** gemapt met `error: mollie_auth_failed` — Consumer ziet "iets achter mij faalt", niet welke schakel.
- Audit-log krijgt `upstream_error: mollie_auth` zodat dashboards op causes kunnen aggregeren.

## Network-error-mapping (504 vs 502)

Een `FatalRequestException` (Mollie's API reageert niet — DNS, timeout, refused) is **operationeel een ander signal** dan een 5xx-respons:

- 504 → check infra/netwerk
- 502 met `upstream_status: 5xx` → check Mollie's status-page
- 502 met `upstream_detail: authentication_failed` → revoked tenant of expired access_token

Aparte status-code maakt dashboard-monitoring scherper.

## Phase 5a-implementatie

```php
// app/Support/Mollie/MollieUpstreamErrorMapper.php (te bouwen in plan-fase)
final class MollieUpstreamErrorMapper
{
    public static function mapException(\Throwable $e): array
    {
        // mirror Snelstart-mapper, match op Emeq\MollieApi\Exceptions\*
        // returns: status, body, headers, short_code
    }
}
```

Single source-of-truth voor zowel HTTP-response als `pass_through_calls.upstream_error`-audit-code. `MolliePassThroughController` doet één `try`-`catch (\Throwable $e)` en gebruikt het resultaat letterlijk.

## Phase 5a-relevantie

- Acceptance: feature-tests per exception-subtype (`MollieApiClient::fake()` + remap-verifie).
- Hub-response-body bevat **nooit** raw Mollie-secret-leakage (`access_token`-prefix in een error-message zou kunnen lekken — SDK doet body-truncation, dus `getMessage()` is veilig om door te geven).
- `_links.documentation.href` uit Mollie's error gaan we **niet** forwarden in de Hub-error-envelope — Consumer hoort de Hub-docs te raadplegen, niet Mollie's direct.
