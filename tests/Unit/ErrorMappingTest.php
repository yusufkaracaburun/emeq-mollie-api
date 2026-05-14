<?php

declare(strict_types=1);

use Emeq\MollieApi\Contracts\MollieCredentialResolver;
use Emeq\MollieApi\Mollie;
use Emeq\MollieApi\Testing\FakeMollieCredentialResolver;
use Mollie\Api\Exceptions\ValidationException;
use Mollie\Api\Fake\MockResponse;
use Mollie\Api\Http\Auth\ApiKeyAuthenticator;
use Mollie\Api\Http\Auth\BearerTokenAuthenticator;
use Mollie\Api\Http\Requests\CreatePaymentRequest;
use Mollie\Api\MollieApiClient;

/*
 * FQCN's geverifieerd in .mollie-fake-fqcn-notes.md (Task 1 van plan 02-07):
 *   - MollieApiClient::fake() is STATIC, retourneert Mollie\Api\Fake\MockMollieClient
 *   - MockResponse::unprocessableEntity($detail, $field) genereert een geldige 422-body
 *   - 422 → Mollie\Api\Http\Middleware\ConvertResponseToException → ValidationException::fromResponse
 *   - BearerTokenAuthenticator::authenticate() zet 'Authorization: Bearer {token}'
 *
 * B-5: omdat fake() STATIC is, gebruikt test 2 fallback-strategie:
 * authenticator-introspect op het resultaat van ONZE app(Mollie::class)->client(),
 * zodat de Bearer-header sluitend gedekt is via authenticator-state + Mollie's
 * eigen vendor-geteste authenticate()-pad.
 */

it('surfaces ValidationException with usable getField() on a 422 response via Mollie::fake()', function (): void {
    // Queue een 422-response voor de CreatePaymentRequest class.
    $mock = MollieApiClient::fake([
        CreatePaymentRequest::class => MockResponse::unprocessableEntity(
            'The amount.value field is required.',
            'amount.value',
        ),
    ]);

    // NB: Mollie's CreatePaymentRequestFactory valideert client-side dat
    // amount.currency + amount.value beide aanwezig zijn (MoneyFactory). De
    // payload moet daar dus doorheen komen zodat de mocked 422-response wordt
    // gehit en ConvertResponseToException 'm in ValidationException omzet.
    // De server-side 'field' in de mock-response is wat getField() retourneert.
    $caught = null;

    try {
        $mock->payments->create([
            'amount'      => ['currency' => 'EUR', 'value' => '0.00'],
            'description' => 'test payment',
            'redirectUrl' => 'https://example.test/return',
        ]);
    } catch (ValidationException $e) {
        $caught = $e;
    }

    expect($caught)->toBeInstanceOf(ValidationException::class)
        ->and($caught->getField())->toBe('amount.value');
});

it('wires the resolved apiKey into an ApiKeyAuthenticator via our Mollie facade (B-5)', function (): void {
    // 1. Bind ONZE resolver met een specifieke test-key (≥30 chars zodat
    //    Mollie's TokenValidator::isApiKey() 'm accepteert).
    $apiKey = 'test_bearer_check_AAAAAAAAAAAAAAAAAAAA';

    $this->app->bind(
        MollieCredentialResolver::class,
        fn () => FakeMollieCredentialResolver::withApiKey($apiKey),
    );
    $this->app->forgetInstance(Mollie::class);

    // 2. Vraag een client via ONZE factory.
    $client = app(Mollie::class)->client();

    expect($client)->toBeInstanceOf(MollieApiClient::class);

    // 3. Onze factory MOET een ApiKeyAuthenticator hebben geïnstalleerd op het
    //    onderliggende MollieApiClient (B-5: bewijs dat de wiring werkt).
    $authenticator = $client->getAuthenticator();

    expect($authenticator)
        ->toBeInstanceOf(ApiKeyAuthenticator::class)
        ->and($authenticator->isTestToken())->toBeTrue();

    // 4. Bewijs dat de EXACT-resolved apiKey in de authenticator zit. Samen met
    //    Mollie's vendor-geteste BearerTokenAuthenticator::authenticate()
    //    (regel: $request->headers()->add('Authorization', "Bearer {$this->token}");)
    //    sluit dit het outgoing-header-contract af zonder netwerk-call.
    $reflection = new ReflectionClass(BearerTokenAuthenticator::class);
    $tokenProp  = $reflection->getProperty('token');
    $tokenProp->setAccessible(true);

    expect($tokenProp->getValue($authenticator))->toBe($apiKey);
});
