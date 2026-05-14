<?php

declare(strict_types=1);

/**
 * Configuratie voor emeq/mollie-api.
 */
return [

    /*
     * Wanneer true (en de Laravel-env is "production"), gooit Mollie::client()
     * een MollieException als de resolved credentials een test_-prefix hebben.
     * Beschermt tegen het per-ongeluk gebruiken van een test-key in productie.
     */
    'enforce_environment' => env('MOLLIE_ENFORCE_ENVIRONMENT', false),

    'http' => [
        /*
         * Guzzle request-timeout in seconden. Wordt gebruikt om een custom
         * Guzzle-client te bouwen die aan MollieApiClient wordt doorgegeven.
         */
        'timeout' => env('MOLLIE_HTTP_TIMEOUT', 30),

        /*
         * Extra Guzzle-options merged op de default. Leeg laten tenzij je
         * proxy/CA-bundle configuratie nodig hebt.
         *
         * @var array<string, mixed>
         */
        'guzzle_options' => [],
    ],

    'idempotency' => [
        /*
         | 'generator' accepteert ofwel:
         |   - een fully-qualified class name implementing
         |     Mollie\Api\Idempotency\IdempotencyKeyGeneratorContract, bv:
         |       'App\\Mollie\\JobAwareIdempotencyGenerator'
         |   - een container alias of binding-key die naar zo'n instance resolved,
         |     bv:
         |       'mollie.idempotency-generator'
         |
         | Mollie::client() roept `$container->make($value)` aan; beide paden
         | resolven naar dezelfde instance. Null = laat Mollie's default
         | generator (random UUID) gebruiken.
         */
        'generator' => null,
    ],

];
