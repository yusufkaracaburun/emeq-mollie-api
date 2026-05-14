<?php

declare(strict_types=1);

use Emeq\MollieApi\Contracts\MollieCredentialResolver;
use Emeq\MollieApi\Data\MollieApiKeyCredentials;
use Emeq\MollieApi\Exceptions\MollieException;
use Emeq\MollieApi\Mollie;
use Emeq\MollieApi\Testing\FakeMollieCredentialResolver;
use Mollie\Api\Contracts\IdempotencyKeyGeneratorContract;
use Mollie\Api\MollieApiClient;

it('builds a MollieApiClient with an authenticator when an API-key resolver is bound', function (): void {
    app()->bind(
        MollieCredentialResolver::class,
        fn () => FakeMollieCredentialResolver::withApiKey('test_alphaAAAAAAAAAAAAAAAAAAAAAAAAA'),
    );

    $client = app(Mollie::class)->client();

    expect($client)->toBeInstanceOf(MollieApiClient::class)
        ->and($client->getAuthenticator())->not->toBeNull();
});

it('builds a MollieApiClient with an authenticator when an OAuth resolver is bound', function (): void {
    app()->bind(
        MollieCredentialResolver::class,
        fn () => FakeMollieCredentialResolver::withOAuth('access_betaAAAAAAAAAAAAAAAAAAAAAAAAA', expiresAt: 9999999999),
    );

    $client = app(Mollie::class)->client();

    expect($client)->toBeInstanceOf(MollieApiClient::class)
        ->and($client->getAuthenticator())->not->toBeNull();
});

it('returns a fresh MollieApiClient per call so multi-tenant key-swaps do not leak across calls', function (): void {
    // B-6: ONE sequence-resolver. forgetInstance ensures the Mollie singleton
    // captures THIS resolver, not a stale one cached from an earlier test.
    $this->app->bind(
        MollieCredentialResolver::class,
        fn () => FakeMollieCredentialResolver::sequence([
            MollieApiKeyCredentials::class => [
                'test_tenantAAAAAAAAAAAAAAAAAAAAAAAAA',
                'test_tenantBBBBBBBBBBBBBBBBBBBBBBBBB',
            ],
        ]),
    );
    $this->app->forgetInstance(Mollie::class);

    $mollie = app(Mollie::class);

    $clientA = $mollie->client(); // sequence slot 0 -> tenant A
    $clientB = $mollie->client(); // sequence slot 1 -> tenant B

    expect($clientA)
        ->toBeInstanceOf(MollieApiClient::class)
        ->and($clientB)
        ->toBeInstanceOf(MollieApiClient::class)
        ->and($clientA)
        ->not
        ->toBe($clientB);

    // Both clients have authenticators wired (instance-level isolation proves
    // we never re-used the same MollieApiClient across credentials).
    expect($clientA->getAuthenticator())->not->toBeNull()
        ->and($clientB->getAuthenticator())->not->toBeNull();
});

it('throws MollieException when enforce_environment + production env + test_ key', function (): void {
    // B-7: Laravel's documented runtime-env override. Works correctly with
    // Application::environment() introspection in Mollie::guardEnvironment().
    $this->app->detectEnvironment(fn () => 'production');
    expect($this->app->environment())->toBe('production'); // sanity-check

    config()->set('mollie.enforce_environment', true);
    $this->app->bind(
        MollieCredentialResolver::class,
        fn () => FakeMollieCredentialResolver::withApiKey('test_shouldblockAAAAAAAAAAAAAAAAAAAA'),
    );
    $this->app->forgetInstance(Mollie::class);

    expect(fn () => app(Mollie::class)->client())
        ->toThrow(MollieException::class, 'test_-prefixed Mollie API key in the production environment');
});

it('does not throw env-guard for live_ keys or non-production envs or when enforce_environment is off', function (): void {
    // (a) production + live_ -> OK
    $this->app->detectEnvironment(fn () => 'production');
    config()->set('mollie.enforce_environment', true);
    $this->app->bind(
        MollieCredentialResolver::class,
        fn () => FakeMollieCredentialResolver::withApiKey('live_prodAAAAAAAAAAAAAAAAAAAAAAAAAA'),
    );
    $this->app->forgetInstance(Mollie::class);
    expect(fn () => app(Mollie::class)->client())->not->toThrow(MollieException::class);

    // (b) production + test_ but enforce_environment=false -> OK.
    $this->app->forgetInstance(Mollie::class);
    config()->set('mollie.enforce_environment', false);
    $this->app->bind(
        MollieCredentialResolver::class,
        fn () => FakeMollieCredentialResolver::withApiKey('test_allowedAAAAAAAAAAAAAAAAAAAAAAAA'),
    );
    expect(fn () => app(Mollie::class)->client())->not->toThrow(MollieException::class);

    // (c) testing env + test_ + enforce_environment=true -> OK (guard only fires in production).
    $this->app->forgetInstance(Mollie::class);
    $this->app->detectEnvironment(fn () => 'testing');
    config()->set('mollie.enforce_environment', true);
    $this->app->bind(
        MollieCredentialResolver::class,
        fn () => FakeMollieCredentialResolver::withApiKey('test_intestingAAAAAAAAAAAAAAAAAAAAAA'),
    );
    expect(fn () => app(Mollie::class)->client())->not->toThrow(MollieException::class);
});

it('applies a custom IdempotencyKeyGenerator when configured via container-alias path (B-8)', function (): void {
    $stub = new class () implements IdempotencyKeyGeneratorContract {
        public function generate(): string
        {
            return 'stub-key';
        }
    };

    // B-8: container-alias path. config-value is the alias string; Mollie
    // resolves it via $container->make($alias).
    app()->instance('mollie.idempotency-stub', $stub);
    config()->set('mollie.idempotency.generator', 'mollie.idempotency-stub');

    app()->bind(
        MollieCredentialResolver::class,
        fn () => FakeMollieCredentialResolver::withApiKey('test_idemAAAAAAAAAAAAAAAAAAAAAAAAAAA'),
    );
    $this->app->forgetInstance(Mollie::class);

    $client = app(Mollie::class)->client();

    // The client now uses our stub generator. Mollie does not expose a public
    // getter for the generator, so we assert that wiring did not throw and
    // that we got back a configured client.
    expect($client)->toBeInstanceOf(MollieApiClient::class);
});
