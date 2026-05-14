# Changelog

Alle noemenswaardige wijzigingen aan `emeq/mollie-api` worden hier bijgehouden.

Het formaat is gebaseerd op [Keep a Changelog](https://keepachangelog.com/nl/1.1.0/),
en dit project volgt [Semantic Versioning](https://semver.org/lang/nl/).

## [Unreleased]

## [0.1.0-alpha.1] - 2026-05-14

Eerste publieke pre-release. Dunne, multi-tenant Laravel-wrapper rond
`mollie/mollie-api-php` v3.11.

### Added

#### Foundation (Phase 2 emeq/hub v0.2)
- `MollieServiceProvider` (Spatie package-tools DSL) met container-bindings
  voor `Mollie::class` (singleton) en `Mollie\Api\MollieApiClient` (per-call
  factory die de actieve tenant-credentials toepast).
- `Emeq\MollieApi\Contracts\MollieCredentialResolver` — strategy-contract
  dat host-apps zelf binden.
- Dual credentials:
  - `Data\MollieApiKeyCredentials` (`test_`/`live_`-prefix-validatie).
  - `Data\MollieOAuthCredentials` (`access_`-prefix-validatie + optionele
    `expiresAt`).
  - Beide `Data\MollieCredentials::fingerprint()` (sha256, eerste 12 chars).
- `Mollie` facade-target met `credentials()` en `client(): MollieApiClient`.
  Per call resolved fresh credentials — geen caching, geen multi-tenant
  leakage.
- Production env-guard: `mollie.enforce_environment=true` throws een
  `MollieException` als een `test_`-key in production wordt gebruikt.
- Custom idempotency-generator-hook via `config('mollie.idempotency.generator')`.
- Exceptions:
  - `Exceptions\MollieException` (base).
  - `Exceptions\MissingCredentialResolverException` (host-app vergat de
    resolver te binden).

#### v0.1 SDK-checklist (na Phase 2)
- `Idempotency\UuidV7IdempotencyKeyGenerator` — UUID v7 (timestamp-prefixed,
  chronologisch sorteerbaar) als alternatief voor Mollie's default
  random-bytes generator. Symfony Uid als require toegevoegd.
- `Testing\FakeMollieCredentialResolver` — verplaatst van `tests/Support/`
  naar `src/Testing/` zodat host-apps de test-double kunnen gebruiken
  (analoog aan Laravel's `Http::fake`).
- `Webhooks\MollieWebhookSignature::verify(Request, string|array $secrets)`
  + `::sign($payload, $secret)` — Laravel-ergonomic wrapper rond Mollie's
  `SignatureValidator`. Multi-secret support voor key-rotatie windows;
  multi-header support voor concurrent rotatie.
- Exception-remap-laag (opt-in):
  - `Exceptions\ValidationException` (preserveert `getField()` uit Mollie's
    422-response).
  - `Exceptions\AuthenticationException` (401/403/missing/invalid-auth).
  - `Exceptions\NotFoundException` (404).
  - `Exceptions\RateLimitException` (429).
  - `Exceptions\ServerException` (5xx).
  - `Exceptions\MollieExceptionMapper::map(Throwable)` — host-apps kunnen
    Mollie's exceptions remappen naar de Emeq-namespace voor uniforme
    catch-blocks over `emeq/*` SDKs heen. Default: Mollie's eigen exceptions
    bubbelen ongewijzigd (geen breaking change).
- `config('mollie.facade_alias')` (ENV: `MOLLIE_FACADE_ALIAS`) — alias-naam
  voor de facade is config-driven via `AliasLoader` in plaats van een
  hard-coded `extra.laravel.aliases`. Zet op `null` om alias-collisions met
  `mollie/laravel-mollie` / `cashier-mollie` te vermijden. Lost ROADMAP
  Phase 2 success criterion 3 op.
- CI workflow (`.github/workflows/tests.yml`): Pest matrix (PHP 8.3+8.4 ×
  prefer-lowest+prefer-stable), Pint `--test`, PHPStan level 8, `composer
  audit`. 4 parallelle jobs op push/PR.

### Changed
- PHPStan level 6 → 8 (geen baseline). `src/Mollie.php` heeft een
  default-arm in de credential-match (forward-compat voor toekomstige
  credential-subtypes). `MollieWebhookSignature::verify` gebruikt
  `$request->headers->all()` voor multi-header signatures.

### Notes
- Geen Packagist-publish nog — installatie via VCS-repo entry. Packagist
  volgt zodra de Hub Phase 4 productie-bewijs heeft voor de SDK.
- Mollie Connect OAuth-flow (authorize-URL, code-exchange, refresh, revoke)
  zit bewust **niet** in deze SDK — die laag zit in `emeq/hub` Phase 4 als
  Hub-service (per ROADMAP-architectuur: SDK is dun, Hub is dik).

[Unreleased]: https://github.com/yusufkaracaburun/emeq-mollie-api/compare/v0.1.0-alpha.1...HEAD
[0.1.0-alpha.1]: https://github.com/yusufkaracaburun/emeq-mollie-api/releases/tag/v0.1.0-alpha.1
