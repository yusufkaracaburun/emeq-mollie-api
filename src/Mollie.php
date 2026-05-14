<?php

declare(strict_types=1);

namespace Emeq\MollieApi;

use Emeq\MollieApi\Contracts\MollieCredentialResolver;
use Emeq\MollieApi\Data\MollieApiKeyCredentials;
use Emeq\MollieApi\Data\MollieCredentials;
use Emeq\MollieApi\Data\MollieOAuthCredentials;
use Emeq\MollieApi\Exceptions\MollieException;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Contracts\Container\Container;
use Mollie\Api\Idempotency\IdempotencyKeyGeneratorContract;
use Mollie\Api\MollieApiClient;

/**
 * Main client + facade target for emeq/mollie-api.
 *
 * Builds per-tenant Mollie\Api\MollieApiClient instances on demand: every call
 * to client() resolves the current credentials via the bound resolver and
 * constructs a fresh MollieApiClient with either setApiKey() (API-key flow)
 * or setAccessToken() (Mollie Connect OAuth flow).
 *
 * Multi-tenancy is achieved by NOT singleton-ing the underlying client — each
 * call resolves credentials anew, so a tenant-switch within the same request
 * lifecycle (e.g. processing multiple jobs in a queue worker) produces a
 * fresh client with the new credentials.
 */
class Mollie
{
    public function __construct(
        private readonly MollieCredentialResolver $resolver,
        private readonly ConfigRepository $config,
        private readonly Container $container,
    ) {
    }

    /**
     * Resolve the credentials for the *current* tenant context. Subsequent
     * calls re-invoke the resolver — useful when the host app switches
     * tenants mid-request (queue worker processing multiple jobs).
     */
    public function credentials(): MollieCredentials
    {
        return $this->resolver->resolve();
    }

    /**
     * Build a fresh Mollie\Api\MollieApiClient configured with the current
     * tenant's credentials. Returns a NEW instance on every call — never
     * cache or share across tenants.
     */
    public function client(): MollieApiClient
    {
        $creds = $this->credentials();

        $this->guardEnvironment($creds);

        $client = new MollieApiClient();

        match (true) {
            $creds instanceof MollieApiKeyCredentials => $client->setApiKey($creds->apiKey),
            $creds instanceof MollieOAuthCredentials  => $client->setAccessToken($creds->accessToken),
        };

        $this->applyIdempotencyGenerator($client);

        return $client;
    }

    /**
     * Apply a custom IdempotencyKeyGenerator if configured.
     *
     * config('mollie.idempotency.generator') accepts ANY of:
     *   (a) a fully-qualified class name implementing IdempotencyKeyGeneratorContract
     *       (e.g. 'App\\Mollie\\JobAwareIdempotencyGenerator')
     *   (b) a container alias / binding key that resolves to such an instance
     *       (e.g. 'mollie.idempotency-generator' bound via $app->bind() or
     *        $app->instance()).
     *
     * Both paths route through $container->make($value), so Laravel's container
     * resolves them identically. We only enforce the resulting object's contract.
     */
    private function applyIdempotencyGenerator(MollieApiClient $client): void
    {
        $value = $this->config->get('mollie.idempotency.generator');

        if (null === $value) {
            return;
        }

        $generator = $this->container->make($value);

        if ( ! $generator instanceof IdempotencyKeyGeneratorContract) {
            throw new MollieException(sprintf(
                'config(mollie.idempotency.generator) must resolve to %s; got %s.',
                IdempotencyKeyGeneratorContract::class,
                is_object($generator) ? $generator::class : gettype($generator),
            ));
        }

        $client->setIdempotencyKeyGenerator($generator);
    }

    /**
     * Production env-guard: refuse to hand out a Mollie client wired with a
     * test_-prefixed API key when running in production AND enforce_environment
     * is on. Prevents accidental use of a test-key against real money.
     *
     * OAuth credentials are never subject to this guard — Mollie's OAuth flow
     * does not have a test/live prefix on the access-token; the test/live mode
     * is determined per-request via the testmode flag on the Mollie call.
     */
    private function guardEnvironment(MollieCredentials $creds): void
    {
        if ( ! $this->config->get('mollie.enforce_environment', false)) {
            return;
        }

        if ('production' !== $this->container->make('app')->environment()) {
            return;
        }

        if ($creds instanceof MollieApiKeyCredentials && $creds->isTestMode()) {
            throw new MollieException(
                'Refusing to use a test_-prefixed Mollie API key in the production environment ' .
                '(mollie.enforce_environment=true). Provide a live_ key.',
            );
        }
    }
}
