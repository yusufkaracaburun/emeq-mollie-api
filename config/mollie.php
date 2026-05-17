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

    /*
     * Class-alias-naam waaronder de Mollie-facade beschikbaar wordt gemaakt
     * via Illuminate\Foundation\AliasLoader. Default 'Mollie' matched de
     * Snelstart-SDK-pattern (alias 'Snelstart').
     *
     * Zet op null om de alias-registratie volledig over te slaan — handig
     * wanneer een host-app óók mollie/laravel-mollie installeert
     * (transitive via cashier-mollie bv) en de alias 'Mollie' al claimt.
     * Gebruik in dat geval de full-FQN: \Emeq\MollieApi\Facades\Mollie.
     *
     * Of zet op een eigen string ('EmeqMollie') om naast laravel-mollie te
     * coexistentën zonder fully-qualified imports.
     */
    'facade_alias' => env('MOLLIE_FACADE_ALIAS', 'Mollie'),

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

    'webhook' => [
        /*
         * Platform-wide signing-secret voor inbound Mollie-webhooks.
         * Mollie tekent payloads met deze secret; verifiër via
         * `Emeq\MollieApi\Webhooks\MollieWebhookSignature::verify(...)`.
         *
         * Hard-fail guard in host-apps: een empty/null secret laat
         * `hash_equals('', '')` true retourneren, dus elke unsigned forgery
         * zou als geldig worden behandeld. Host moet 500 returnen wanneer
         * deze waarde ontbreekt.
         */
        'secret' => env('MOLLIE_WEBHOOK_SECRET'),
    ],

];
