# emeq/mollie-api

> Multi-tenant Laravel SDK rond [`mollie/mollie-api-php`](https://github.com/mollie/mollie-api-php) — credential resolver, dual credentials (API key + Mollie Connect OAuth) en een facade.

Een dunne wrapper-laag die per request fresh `Mollie\Api\MollieApiClient`-instances bouwt op basis van een host-app-bound `MollieCredentialResolver`. Resource-calls (`payments`, `customers`, `subscriptions`, etc.) routeer je rechtstreeks naar het onderliggende `mollie/mollie-api-php`.

## Installatie

Tot een eerste Packagist-release loopt de installatie via een VCS-repository:

```jsonc
// composer.json van de host-app
{
    "repositories": [
        {
            "type": "vcs",
            "url": "https://github.com/yusufkaracaburun/emeq-mollie-api"
        }
    ],
    "require": {
        "emeq/mollie-api": "dev-feat/foundation"
    }
}
```

```bash
composer require emeq/mollie-api:dev-feat/foundation
```

## Quick-start

1. Maak een eigen `MollieCredentialResolver` die per request de juiste credentials teruggeeft:

```php
namespace App\Mollie;

use Emeq\MollieApi\Contracts\MollieCredentialResolver;
use Emeq\MollieApi\Data\MollieApiKeyCredentials;
use Emeq\MollieApi\Data\MollieCredentials;
use Emeq\MollieApi\Data\MollieOAuthCredentials;

final class TenantMollieCredentialResolver implements MollieCredentialResolver
{
    public function __construct(private readonly TenantContext $context) {}

    public function resolve(): MollieCredentials
    {
        $connection = $this->context->currentConnection();

        return $connection->isOAuth()
            ? new MollieOAuthCredentials($connection->access_token, $connection->expires_at?->getTimestamp())
            : new MollieApiKeyCredentials($connection->api_key);
    }
}
```

2. Bind 'm in een `ServiceProvider`:

```php
namespace App\Providers;

use App\Mollie\TenantMollieCredentialResolver;
use Emeq\MollieApi\Contracts\MollieCredentialResolver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(MollieCredentialResolver::class, TenantMollieCredentialResolver::class);
    }
}
```

3. Roep de Mollie-API aan:

```php
use Emeq\MollieApi\Facades\Mollie;

$payment = Mollie::client()->payments->create([
    'amount'      => ['currency' => 'EUR', 'value' => '12.50'],
    'description' => 'Order #1234',
    'redirectUrl' => route('checkout.return'),
    'webhookUrl'  => route('webhooks.mollie'),
]);
```

`Mollie::client()` retourneert per call een fresh `Mollie\Api\MollieApiClient` met de credentials van de *huidige* tenant — geen caching, geen leakage.

## Multi-tenant: meerdere connections in één request

Switch je context (bv. queue-job, command) en roep gewoon opnieuw `Mollie::client()` aan — de resolver wordt elke keer opnieuw aangeroepen:

```php
foreach ($connections as $connection) {
    app(TenantContext::class)->use($connection);

    $balances = Mollie::client()->balances->page();
    // verwerk de balances voor deze tenant…
}
```

## Dual credentials

`MollieCredentialResolver::resolve()` retourneert óf een `MollieApiKeyCredentials` óf een `MollieOAuthCredentials`. De SDK swapped intern tussen `setApiKey()` en `setAccessToken()` — host-apps hoeven niet te weten welk type credential terugkomt.

| Class                      | Prefix-validatie | Use-case                                 |
|----------------------------|------------------|------------------------------------------|
| `MollieApiKeyCredentials`  | `test_` / `live_`| Eigen Mollie-account (B2C SaaS)          |
| `MollieOAuthCredentials`   | `access_`        | Mollie Connect — eindgebruiker-accounts  |

Beide credentials hebben een `fingerprint(): string` (sha256, eerste 12 chars) voor audit-logs zonder de raw secret te lekken.

## Webhook-signature verificatie

`MollieWebhookSignature::verify` wrapt Mollie's `SignatureValidator` ergonomisch rond een Laravel `Request`:

```php
use Emeq\MollieApi\Webhooks\MollieWebhookSignature;
use Mollie\Api\Exceptions\InvalidSignatureException;

Route::post('/webhooks/mollie', function (Request $request) {
    try {
        $valid = MollieWebhookSignature::verify($request, config('services.mollie.webhook_secret'));
    } catch (InvalidSignatureException) {
        abort(403);
    }

    if (! $valid) {
        // legacy webhook zonder signature-header — accepteer alleen tijdens migratie
        return response()->noContent();
    }

    // verwerk de webhook…
});
```

Multi-secret (key-rotatie):

```php
MollieWebhookSignature::verify($request, [$oldSecret, $newSecret]);
```

## Exception-handling

Standaard bubbelen `Mollie\Api\Exceptions\*` ongewijzigd door — host-apps catchen die direct. Voor host-apps die uniforme catch-blocks willen over meerdere `emeq/*` SDKs (snelstart-api gebruikt dezelfde subtype-namen) is er een opt-in mapper:

```php
use Emeq\MollieApi\Exceptions\MollieExceptionMapper;
use Emeq\MollieApi\Exceptions\ValidationException;
use Mollie\Api\Exceptions\ApiException;

try {
    Mollie::client()->payments->create([...]);
} catch (ApiException $e) {
    throw MollieExceptionMapper::map($e);
}

// Vervolgens kun je host-side catchen op:
//   Emeq\MollieApi\Exceptions\ValidationException   (422 + ->getField())
//   Emeq\MollieApi\Exceptions\AuthenticationException (401 / 403)
//   Emeq\MollieApi\Exceptions\NotFoundException     (404)
//   Emeq\MollieApi\Exceptions\RateLimitException    (429)
//   Emeq\MollieApi\Exceptions\ServerException       (5xx)
//   Emeq\MollieApi\Exceptions\MollieException       (base, fallback)
```

## Configuratie

`config/mollie.php` (publiceren met `php artisan vendor:publish --tag="mollie-config"`):

| Key                              | ENV                          | Default     | Doel |
|----------------------------------|------------------------------|-------------|------|
| `enforce_environment`            | `MOLLIE_ENFORCE_ENVIRONMENT` | `false`     | Throws als een `test_`-key in production wordt gebruikt. |
| `facade_alias`                   | `MOLLIE_FACADE_ALIAS`        | `"Mollie"`  | Class-alias voor de facade. Zet op `null`/`""` om te skippen. |
| `http.timeout`                   | `MOLLIE_HTTP_TIMEOUT`        | `30`        | Guzzle request-timeout (seconden). |
| `http.guzzle_options`            | —                            | `[]`        | Extra Guzzle-options (proxy, CA-bundle, etc.). |
| `idempotency.generator`          | —                            | `null`      | FQCN of container-alias voor een custom `IdempotencyKeyGeneratorContract`. |

## Idempotency-keys

Mollie's default-generator (`random_bytes`) is voor de meeste apps prima. Voor sortable keys (handig in audit-logs) levert deze package een UUID-v7-generator:

```php
// config/mollie.php
'idempotency' => [
    'generator' => \Emeq\MollieApi\Idempotency\UuidV7IdempotencyKeyGenerator::class,
],
```

Of bind 'm via een container-alias:

```php
$this->app->bind('mollie.idempotency-generator', UuidV7IdempotencyKeyGenerator::class);
// in config:
'generator' => 'mollie.idempotency-generator',
```

## Testing in host-apps

De SDK ships een `FakeMollieCredentialResolver` voor host-app-tests:

```php
use Emeq\MollieApi\Contracts\MollieCredentialResolver;
use Emeq\MollieApi\Data\MollieApiKeyCredentials;
use Emeq\MollieApi\Testing\FakeMollieCredentialResolver;

it('creates a payment for the active tenant', function () {
    $this->app->bind(
        MollieCredentialResolver::class,
        fn () => FakeMollieCredentialResolver::withApiKey('test_AAAAAAAAAAAAAAAAAAAAAAAAAAAAAA'),
    );

    // Combineer met Mollie's eigen MollieApiClient::fake([...]) voor mocked responses.
});
```

Sequence-modus voor multi-tenant tests:

```php
FakeMollieCredentialResolver::sequence([
    MollieApiKeyCredentials::class => ['test_a_AAAAAAAAAAAAAAAAAAAAAA', 'test_b_BBBBBBBBBBBBBBBBBBBBBB'],
]);
// resolve() retourneert eerst test_a, daarna test_b, dan weer test_a, etc.
```

## Facade-alias collision

Wanneer een host-app naast deze SDK óók [`mollie/laravel-mollie`](https://github.com/mollie/laravel-mollie) gebruikt (bijvoorbeeld transitive via [`mollie/laravel-cashier-mollie`](https://github.com/mollie/laravel-cashier-mollie)) ontstaat er een alias-conflict op de korte naam `Mollie` in `config/app.php → aliases`.

**Oplossing:** zet `MOLLIE_FACADE_ALIAS` op een eigen string of `null` (zie [Configuratie](#configuratie)).

## Onderdeel van

Deze SDK is onderdeel van [`emeq/hub`](https://github.com/yusufkaracaburun/emeq-hub) — een integratie-platform dat Mollie + Snelstart + andere NL providers multi-tenant aanbiedt. Voor de Hub-laag (OAuth-broker, Connection-storage, pass-through API) zie die repo.

## License

MIT — zie [LICENSE.md](LICENSE.md).
