<?php

declare(strict_types=1);

namespace Emeq\MollieApi\Testing;

use Emeq\MollieApi\Contracts\MollieCredentialResolver;
use Emeq\MollieApi\Data\MollieApiKeyCredentials;
use Emeq\MollieApi\Data\MollieCredentials;
use Emeq\MollieApi\Data\MollieOAuthCredentials;
use InvalidArgumentException;

/**
 * Test-double for MollieCredentialResolver.
 *
 * Three construction modes:
 *  - FakeMollieCredentialResolver::withApiKey('test_xxx')
 *  - FakeMollieCredentialResolver::withOAuth('access_xxx', expiresAt: 9999)
 *  - FakeMollieCredentialResolver::sequence([cred1, cred2, ...])
 *
 * Or constructed directly with one-or-more MollieCredentials instances.
 *
 * For multi-tenant resolver tests where the credentials change between
 * resolve() calls, use ::sequence([...]) and the resolver returns each
 * credential in turn (cycling back to the start once exhausted).
 *
 * Or use the API-key-shortcut sequence:
 *   FakeMollieCredentialResolver::sequence([
 *       MollieApiKeyCredentials::class => ['test_a', 'test_b'],
 *   ])
 * (handy for plan 02-06 multi-tenant key-swap test, B-6).
 */
final class FakeMollieCredentialResolver implements MollieCredentialResolver
{
    /** @var list<MollieCredentials> */
    private array $sequence;

    private int $index = 0;

    public function __construct(MollieCredentials ...$credentials)
    {
        if ([] === $credentials) {
            throw new InvalidArgumentException('FakeMollieCredentialResolver needs at least one credential.');
        }

        $this->sequence = array_values($credentials);
    }

    public static function withApiKey(string $apiKey = 'test_fake_default'): self
    {
        return new self(new MollieApiKeyCredentials($apiKey));
    }

    public static function withOAuth(string $accessToken = 'access_fake_default', ?int $expiresAt = null): self
    {
        return new self(new MollieOAuthCredentials($accessToken, $expiresAt));
    }

    /**
     * Accepts either:
     *  - a plain list<MollieCredentials>, e.g. [new MollieApiKeyCredentials('test_a'), ...]
     *  - a shortcut map { FQCN => list<string> } for API-key/access-token strings:
     *      [MollieApiKeyCredentials::class => ['test_a', 'test_b']]
     *      [MollieOAuthCredentials::class  => ['access_a', 'access_b']]
     *
     * @param  list<MollieCredentials>|array<class-string<MollieCredentials>, list<string>>  $credentials
     */
    public static function sequence(array $credentials): self
    {
        $instances = [];

        foreach ($credentials as $key => $value) {
            // Shortcut-map form: keyed by FQCN, value is list<string>.
            if (is_string($key) && is_array($value)) {
                $fqcn = $key;

                foreach ($value as $raw) {
                    $instances[] = match ($fqcn) {
                        MollieApiKeyCredentials::class => new MollieApiKeyCredentials($raw),
                        MollieOAuthCredentials::class  => new MollieOAuthCredentials($raw),
                        default                        => throw new InvalidArgumentException(
                            sprintf('Unsupported credential class in sequence shortcut: %s', $fqcn),
                        ),
                    };
                }

                continue;
            }

            // Plain list form.
            if ($value instanceof MollieCredentials) {
                $instances[] = $value;
            }
        }

        return new self(...$instances);
    }

    public function resolve(): MollieCredentials
    {
        $credentials = $this->sequence[$this->index % count($this->sequence)];
        $this->index++;

        return $credentials;
    }
}
