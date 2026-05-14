<?php

declare(strict_types=1);

use Emeq\MollieApi\Contracts\MollieCredentialResolver;
use Emeq\MollieApi\Data\MollieApiKeyCredentials;
use Emeq\MollieApi\Exceptions\MissingCredentialResolverException;
use Emeq\MollieApi\Facades\Mollie as MollieFacade;
use Emeq\MollieApi\Mollie;
use Emeq\MollieApi\MollieServiceProvider;
use Emeq\MollieApi\Tests\Support\FakeMollieCredentialResolver;
use Mollie\Api\MollieApiClient;

it('registers the service provider', function (): void {
    $provider = app()->getProvider(MollieServiceProvider::class);

    expect($provider)->toBeInstanceOf(MollieServiceProvider::class);
});

it('publishes the mollie config with expected defaults', function (): void {
    expect(config('mollie.enforce_environment'))->toBeFalse()
        ->and(config('mollie.http.timeout'))->toBe(30)
        ->and(config('mollie.idempotency.generator'))->toBeNull();
});

it('throws MissingCredentialResolverException when no resolver is bound', function (): void {
    expect(fn () => app(Mollie::class))
        ->toThrow(
            MissingCredentialResolverException::class,
            'No ' . MollieCredentialResolver::class . ' binding found',
        );
});

it('resolves the main Mollie facade-target when a resolver is bound', function (): void {
    // Mollie's TokenValidator requires keys to be ≥30 chars; using a long
    // fixture so the same key works in both credentials-only assertions and
    // the client-instantiation test below.
    $apiKey = 'test_smokeAAAAAAAAAAAAAAAAAAAAAAAAA';
    app()->bind(MollieCredentialResolver::class, fn () => FakeMollieCredentialResolver::withApiKey($apiKey));

    $mollie = app(Mollie::class);

    $resolved = $mollie->credentials();

    expect($mollie)->toBeInstanceOf(Mollie::class)
        ->and($resolved)->toBeInstanceOf(MollieApiKeyCredentials::class)
        ->and($resolved->apiKey)->toBe($apiKey)
        ->and($resolved->fingerprint())->toBe(substr(hash('sha256', $apiKey), 0, 12));
});

it('resolves the Mollie facade through the container', function (): void {
    $apiKey = 'test_facadeAAAAAAAAAAAAAAAAAAAAAAAAA';
    app()->bind(MollieCredentialResolver::class, fn () => FakeMollieCredentialResolver::withApiKey($apiKey));

    expect(MollieFacade::getFacadeRoot())->toBeInstanceOf(Mollie::class);
});

it('binds MollieApiClient as a non-singleton (fresh per resolve)', function (): void {
    $apiKey = 'test_perresolveAAAAAAAAAAAAAAAAAAAAA';
    app()->bind(MollieCredentialResolver::class, fn () => FakeMollieCredentialResolver::withApiKey($apiKey));

    $a = app(MollieApiClient::class);
    $b = app(MollieApiClient::class);

    expect($a)->toBeInstanceOf(MollieApiClient::class)
        ->and($b)->toBeInstanceOf(MollieApiClient::class)
        ->and($a)->not->toBe($b);
});
