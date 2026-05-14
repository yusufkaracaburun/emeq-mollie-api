<?php

declare(strict_types=1);

namespace Emeq\MollieApi\Exceptions;

use RuntimeException;

/**
 * Base exception for every error raised by emeq/mollie-api itself
 * (i.e. package-level wiring/config errors).
 *
 * Errors die uit mollie/mollie-api-php komen (HTTP 4xx/5xx) worden standaard
 * NIET automatisch gewrapped — Mollie's eigen exceptions bubbelen door:
 *  - Mollie\Api\Exceptions\ApiException                  (generic)
 *  - Mollie\Api\Exceptions\ValidationException           (422 + ::getField())
 *  - Mollie\Api\Exceptions\UnauthorizedException         (401)
 *  - Mollie\Api\Exceptions\ForbiddenException            (403)
 *  - Mollie\Api\Exceptions\NotFoundException             (404)
 *  - Mollie\Api\Exceptions\TooManyRequestsException      (429)
 *  - Mollie\Api\Exceptions\ServerException               (5xx)
 *  - Mollie\Api\Exceptions\ServiceUnavailableException   (503)
 *
 * Host-apps die uniforme catch-blocks willen over meerdere emeq/* SDKs heen
 * (snelstart-api gebruikt dezelfde subtype-namen) kunnen MollieExceptionMapper
 * gebruiken om Mollie's exceptions te remappen naar de Emeq-hiërarchie.
 *
 * MollieException subclasses:
 *  - MissingCredentialResolverException — host-app heeft de resolver niet gebound
 *  - ValidationException                — 422 (matched Mollie's ::getField())
 *  - AuthenticationException            — 401 / 403 / missing-auth
 *  - NotFoundException                  — 404
 *  - RateLimitException                 — 429
 *  - ServerException                    — 5xx
 */
class MollieException extends RuntimeException
{
}
