<?php

declare(strict_types=1);

namespace Emeq\MollieApi\Webhooks;

use Illuminate\Http\Request;
use Mollie\Api\Exceptions\InvalidSignatureException;
use Mollie\Api\Webhooks\SignatureValidator;

/**
 * Laravel-ergonomic wrapper rond Mollie's PSR-7-based SignatureValidator.
 *
 * Host-apps schrijven:
 *
 *     MollieWebhookSignature::verify($request, config('services.mollie.webhook_secret'));
 *
 * en hoeven niet zelf body + header uit een Illuminate\Http\Request te plukken
 * of een PSR-7-bridge te draaien.
 */
final class MollieWebhookSignature
{
    /**
     * Verify de X-Mollie-Signature header tegen één of meer signing-secrets.
     *
     * @param  string|string[]  $signingSecrets  Eén secret of meerdere (handig
     *                                           tijdens een key-rotatie window).
     * @return bool  true = geldige signature; false = legacy webhook zonder header.
     *
     * @throws InvalidSignatureException  Alle signatures matchten geen secret.
     */
    public static function verify(Request $request, string|array $signingSecrets): bool
    {
        $signatureHeaders = $request->header(SignatureValidator::SIGNATURE_HEADER);

        if (null === $signatureHeaders || [] === $signatureHeaders) {
            return false;
        }

        return (new SignatureValidator($signingSecrets))->validatePayload(
            payload: $request->getContent(),
            signatures: is_array($signatureHeaders) ? $signatureHeaders : [$signatureHeaders],
        );
    }

    /**
     * Genereer een geldige X-Mollie-Signature voor een payload (handig voor tests
     * of voor het signeren van outgoing webhooks die je zelf doorstuurt).
     */
    public static function sign(string $payload, string $signingSecret): string
    {
        return 'sha256=' . hash_hmac('sha256', $payload, $signingSecret);
    }
}
