<?php

declare(strict_types=1);

use Emeq\MollieApi\Webhooks\MollieWebhookSignature;
use Illuminate\Http\Request;
use Mollie\Api\Exceptions\InvalidSignatureException;
use Mollie\Api\Webhooks\SignatureValidator;

it('signs and verifies a payload roundtrip with a single secret', function (): void {
    $payload = '{"id":"tr_xxx","resource":"payment"}';
    $secret  = 'sk_test_signing_secret';

    $signature = MollieWebhookSignature::sign($payload, $secret);

    $request = Request::create('/webhooks/mollie', 'POST', content: $payload);
    $request->headers->set(SignatureValidator::SIGNATURE_HEADER, $signature);

    expect(MollieWebhookSignature::verify($request, $secret))->toBeTrue();
});

it('returns false for legacy webhooks without a signature header', function (): void {
    $request = Request::create('/webhooks/mollie', 'POST', content: '{"id":"tr_xxx"}');

    expect(MollieWebhookSignature::verify($request, 'sk_secret'))->toBeFalse();
});

it('throws InvalidSignatureException when the signature does not match', function (): void {
    $payload = '{"id":"tr_xxx"}';
    $request = Request::create('/webhooks/mollie', 'POST', content: $payload);
    $request->headers->set(SignatureValidator::SIGNATURE_HEADER, 'sha256=deadbeef');

    MollieWebhookSignature::verify($request, 'sk_real_secret');
})->throws(InvalidSignatureException::class);

it('accepts an array of signing secrets and matches any', function (): void {
    $payload   = '{"id":"tr_xxx"}';
    $oldSecret = 'sk_test_old';
    $newSecret = 'sk_test_new';

    $request = Request::create('/webhooks/mollie', 'POST', content: $payload);
    $request->headers->set(SignatureValidator::SIGNATURE_HEADER, MollieWebhookSignature::sign($payload, $oldSecret));

    expect(MollieWebhookSignature::verify($request, [$oldSecret, $newSecret]))->toBeTrue();
});

it('sign() returns the sha256=<hex> prefix expected by Mollie', function (): void {
    $signature = MollieWebhookSignature::sign('payload', 'secret');

    expect($signature)->toStartWith('sha256=')
        ->and(substr($signature, 7))->toMatch('/^[0-9a-f]{64}$/');
});
