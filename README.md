# emeq/mollie-api

> Multi-tenant Laravel SDK rond [`mollie/mollie-api-php`](https://github.com/mollie/mollie-api-php) — credential resolver, dual credentials (API key + Mollie Connect OAuth) en een facade.

## Installatie

```bash
composer require emeq/mollie-api
```

## Gebruik

```php
use Emeq\MollieApi\Contracts\MollieCredentialResolver;
use Emeq\MollieApi\Data\MollieApiKeyCredentials;
use Emeq\MollieApi\Facades\Mollie;

// 1. Bind een resolver (in een ServiceProvider van de host-app)
app()->bind(MollieCredentialResolver::class, fn () => new YourTenantResolver());

// 2. Roep de Mollie facade aan
$client = Mollie::client(); // \Mollie\Api\MollieApiClient
$payment = $client->payments->create([...]);
```

### Dual credentials

Een `MollieCredentialResolver::resolve()` returnt óf `MollieApiKeyCredentials` óf `MollieOAuthCredentials`. De SDK swapped runtime tussen `setApiKey()` (API-key flow) en `setAccessToken()` (Mollie Connect OAuth flow) zonder dat de host-app hoeft te weten welk type credential terugkomt.

### Facade-alias collision

`emeq/mollie-api` registreert een facade onder de alias-naam `Mollie`. Dit matched de Snelstart-SDK-pattern (`Snelstart` alias) en is consistent met de verdere `emeq/*` SDK-laag.

Wanneer een host-app naast deze SDK óók [`mollie/laravel-mollie`](https://github.com/mollie/laravel-mollie) gebruikt (bijvoorbeeld transitive via [`mollie/laravel-cashier-mollie`](https://github.com/mollie/laravel-cashier-mollie)) ontstaat er een alias-conflict op de korte naam `Mollie` in `config/app.php → aliases`.

**Status in Phase 2 (v0.2):** Phase 2 levert dit op met de `Mollie`-alias zonder ingebouwde detectie of compat-laag voor laravel-mollie. Host-apps die beide pakketten gebruiken moeten één van twee:

1. Verwijder de auto-registered `Mollie`-alias van laravel-mollie en gebruik volledig gekwalificeerde imports (`use Mollie\Laravel\Facades\Mollie as LaravelMollie;`), of
2. Disable auto-discovery voor één van beide packages en bind de facade handmatig onder een eigen alias.

**Status in Phase 6 (SUB-01):** Phase 6's SUB-01 levert een eerste-class collision-resolutie. De ROADMAP Phase 2 success criterion 3 ("emeq/mollie-api en mollie/laravel-mollie functioneren naast elkaar zonder conflict") wordt in Phase 2 daarom NIET via tests gevalideerd — die dekking komt in Phase 6.

Beslissingsspoor (CONTEXT.md decision, 2026-05-14): de alias blijft `Mollie` omdat consistency met Snelstart-pattern zwaarder weegt dan future-proofing voor laravel-mollie-coexistentie in Phase 2.

## Onderdeel van

Deze SDK is onderdeel van [`emeq/hub`](https://github.com/yusufkaracaburun/emeq-hub) — een integratie-platform dat Mollie + Snelstart + andere NL providers multi-tenant aanbiedt.

## License

MIT — zie [LICENSE.md](LICENSE.md).
