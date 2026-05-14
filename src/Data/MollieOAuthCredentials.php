<?php

declare(strict_types=1);

namespace Emeq\MollieApi\Data;

use InvalidArgumentException;

/**
 * Mollie Connect OAuth credentials.
 *
 * Used when a tenant connected their Mollie account to Emeq through the
 * Mollie Connect OAuth flow (Phase 4). The access-token has an `access_`
 * prefix and a finite lifetime; the Hub's OAuthFlow refreshes the token
 * before expiry, but this Data class only carries the currently-valid
 * access-token + its expiry timestamp.
 *
 * Refresh-token handling and storage live in the Hub's OAuthFlow
 * implementation, not in this SDK package.
 *
 * Validation uses PHP-core trim() (NOT mb_trim()) for PHP ^8.3 compat.
 */
final readonly class MollieOAuthCredentials extends MollieCredentials
{
    public function __construct(
        public string $accessToken,
        public ?int $expiresAt = null,
    ) {
        if ('' === trim($this->accessToken)) {
            throw new InvalidArgumentException('MollieOAuthCredentials: accessToken may not be empty.');
        }

        if ( ! str_starts_with($this->accessToken, 'access_')) {
            throw new InvalidArgumentException(
                'MollieOAuthCredentials: accessToken must start with "access_".',
            );
        }
    }

    /**
     * @param  array{accessToken: string, expiresAt?: ?int}  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            accessToken: $data['accessToken'],
            expiresAt: $data['expiresAt'] ?? null,
        );
    }

    protected function getSecretMaterial(): string
    {
        return $this->accessToken;
    }
}
