<?php

declare(strict_types=1);

namespace Emeq\MollieApi\Exceptions;

use RuntimeException;

/**
 * Base exception for every error raised by emeq/mollie-api itself
 * (i.e. package-level wiring/config errors, NOT HTTP-level errors).
 *
 * Errors that originate from the underlying mollie/mollie-api-php library
 * (HTTP 4xx/5xx, validation, rate-limiting, etc.) are surfaced as the
 * library's own exceptions:
 *  - Mollie\Api\Exceptions\ApiException                  (generic)
 *  - Mollie\Api\Exceptions\ValidationException           (422 + ::getField())
 *  - Mollie\Api\Exceptions\UnauthorizedException         (401)
 *  - Mollie\Api\Exceptions\NotFoundException             (404)
 *  - Mollie\Api\Exceptions\TooManyRequestsException      (429)
 *  - Mollie\Api\Exceptions\ServerException               (5xx)
 *  - Mollie\Api\Exceptions\ServiceUnavailableException   (503)
 *
 * Host apps catch those directly. We do NOT wrap them in our own hierarchy.
 *
 * MollieException subclasses (in this package):
 *  - MissingCredentialResolverException — host app forgot to bind the resolver
 *  - (future) production-env-guard violations when enforce_environment=true
 */
class MollieException extends RuntimeException
{
}
