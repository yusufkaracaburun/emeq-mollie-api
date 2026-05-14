<?php

declare(strict_types=1);

namespace Emeq\MollieApi\Data;

/**
 * Abstract base for Mollie credentials. Two concrete shapes:
 *  - MollieApiKeyCredentials  — `apiKey` with test_|live_ prefix
 *  - MollieOAuthCredentials   — `accessToken` with access_ prefix
 *
 * The MollieCredentialResolver returns one of these per request; Mollie::client()
 * branches on the concrete type to choose between setApiKey() and setAccessToken().
 *
 * Security: the raw secret material is intentionally NOT exposed via a public
 * base-class method. The only public hash-pad is fingerprint(); the protected
 * abstract getSecretMaterial() gives the base enough information to compute
 * fingerprint() without leaking the secret outside the class hierarchy.
 * Subclasses still expose their concrete typed public property (e.g. $apiKey
 * or $accessToken) — that is where intentional, type-narrowed access lives.
 */
abstract readonly class MollieCredentials
{
    /**
     * Returns the underlying raw secret used as hash-input for fingerprint().
     * PROTECTED on purpose: callers outside the class hierarchy must use the
     * concrete subclass's typed public property if they need the actual secret.
     */
    abstract protected function getSecretMaterial(): string;

    /**
     * Stable, non-reversible identifier for log/audit/cache-key derivation.
     * Returns the first 12 chars of sha256($secretMaterial) — sufficient to
     * identify a credential without leaking the underlying secret.
     */
    public function fingerprint(): string
    {
        return substr(hash('sha256', $this->getSecretMaterial()), 0, 12);
    }
}
