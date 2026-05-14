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

**Oplossing:** de alias-naam is config-driven via `config('mollie.facade_alias')` (ENV: `MOLLIE_FACADE_ALIAS`):

- Default `"Mollie"` — matched de Snelstart-pattern.
- Custom string (bv. `"EmeqMollie"`) — coexistentie met `laravel-mollie` zonder dat één van beide aliases overschreven wordt.
- `null` of `""` — geen alias geregistreerd; gebruik de full-FQN `\Emeq\MollieApi\Facades\Mollie` in imports.

```bash
# .env van een host-app die ook laravel-mollie gebruikt:
MOLLIE_FACADE_ALIAS=EmeqMollie
```

## Onderdeel van

Deze SDK is onderdeel van [`emeq/hub`](https://github.com/yusufkaracaburun/emeq-hub) — een integratie-platform dat Mollie + Snelstart + andere NL providers multi-tenant aanbiedt.

## License

MIT — zie [LICENSE.md](LICENSE.md).
