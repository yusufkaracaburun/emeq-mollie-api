<?php

declare(strict_types=1);

namespace Emeq\MollieApi\Idempotency;

use Emeq\MollieApi\Exceptions\MollieException;
use Mollie\Api\Contracts\IdempotencyKeyGeneratorContract;
use Symfony\Component\Uid\Uuid;

/**
 * UUID v7 idempotency-key generator.
 *
 * UUID v7 is timestamp-prefixed (Unix-millis) so the keys sort chronologically
 * in logs and audit-tables — handy when correlating idempotent retries to the
 * original request. Falls back to base64(random_bytes) only if symfony/uid is
 * unexpectedly missing (the package require's it; the check exists so the
 * generator gives a clear error when used in a strange autoload setup).
 */
final class UuidV7IdempotencyKeyGenerator implements IdempotencyKeyGeneratorContract
{
    public function generate(): string
    {
        if ( ! class_exists(Uuid::class)) {
            throw new MollieException(
                'symfony/uid is not installed but required by ' . self::class . '. ' .
                'Run `composer require symfony/uid` or remove this generator from config(mollie.idempotency.generator).',
            );
        }

        return Uuid::v7()->toRfc4122();
    }
}
