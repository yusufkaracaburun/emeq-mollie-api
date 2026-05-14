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

Bij gebruik naast [`mollie/laravel-mollie`](https://github.com/mollie/laravel-mollie) (bijv. transitive via Cashier-Mollie) moet één van beide aliases worden uitgeschakeld of FQN-imported. Zie [docs/integraties](#) (volgt in Phase 6 — SUB-01).

## Onderdeel van

Deze SDK is onderdeel van [`emeq/hub`](https://github.com/yusufkaracaburun/emeq-hub) — een integratie-platform dat Mollie + Snelstart + andere NL providers multi-tenant aanbiedt.

## License

MIT — zie [LICENSE.md](LICENSE.md).
