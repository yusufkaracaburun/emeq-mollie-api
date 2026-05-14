<?php

declare(strict_types=1);

namespace Emeq\MollieApi\Data;

use InvalidArgumentException;

/**
 * Mollie API-key credentials.
 *
 * Used when a tenant authenticates with a direct Mollie API-key (i.e. they
 * have their own Mollie account and granted Emeq the raw key, or it is
 * Emeq's own Mollie account).
 *
 * Mollie's keys are prefixed: `test_xxx` for the test mode, `live_xxx` for
 * production. Both prefixes are accepted at construction time; the ServiceProvider
 * may additionally enforce that production environments use `live_` keys
 * (see config/mollie.php `enforce_environment`).
 *
 * Validation uses PHP-core trim() (NOT mb_trim()) to remain compatible with
 * the package's PHP ^8.3 constraint — mb_trim() was added in PHP 8.4.
 */
final readonly class MollieApiKeyCredentials extends MollieCredentials
{
    public function __construct(
        public string $apiKey,
    ) {
        if ('' === trim($this->apiKey)) {
            throw new InvalidArgumentException('MollieApiKeyCredentials: apiKey may not be empty.');
        }

        if ( ! str_starts_with($this->apiKey, 'test_') && ! str_starts_with($this->apiKey, 'live_')) {
            throw new InvalidArgumentException(
                'MollieApiKeyCredentials: apiKey must start with "test_" or "live_".',
            );
        }
    }

    /**
     * @param  array{apiKey: string}  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(apiKey: $data['apiKey']);
    }

    public function isTestMode(): bool
    {
        return str_starts_with($this->apiKey, 'test_');
    }

    protected function getSecretMaterial(): string
    {
        return $this->apiKey;
    }
}
