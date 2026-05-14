<?php

declare(strict_types=1);

namespace Emeq\MollieApi\Exceptions;

/**
 * Maps 401 / 403 + missing/invalid-auth-token errors van mollie/mollie-api-php.
 *
 * Bron-exceptions die hierin remapped kunnen worden:
 *  - Mollie\Api\Exceptions\UnauthorizedException        (401)
 *  - Mollie\Api\Exceptions\ForbiddenException           (403)
 *  - Mollie\Api\Exceptions\MissingAuthenticationException
 *  - Mollie\Api\Exceptions\InvalidAuthenticationException
 */
final class AuthenticationException extends MollieException
{
}
