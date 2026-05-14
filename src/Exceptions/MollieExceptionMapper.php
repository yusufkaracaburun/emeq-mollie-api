<?php

declare(strict_types=1);

namespace Emeq\MollieApi\Exceptions;

use Mollie\Api\Exceptions\ApiException as MollieApiException;
use Mollie\Api\Exceptions\ForbiddenException as MollieForbiddenException;
use Mollie\Api\Exceptions\InvalidAuthenticationException as MollieInvalidAuthException;
use Mollie\Api\Exceptions\MissingAuthenticationException as MollieMissingAuthException;
use Mollie\Api\Exceptions\NotFoundException as MollieNotFoundException;
use Mollie\Api\Exceptions\ServerException as MollieServerException;
use Mollie\Api\Exceptions\TooManyRequestsException as MollieRateLimitException;
use Mollie\Api\Exceptions\UnauthorizedException as MollieUnauthorizedException;
use Mollie\Api\Exceptions\ValidationException as MollieValidationException;
use Throwable;

/**
 * Optionele remap-laag tussen mollie/mollie-api-php en Emeq\MollieApi\Exceptions\*.
 *
 * Default heeft de SDK een non-wrapping policy (zie MollieException docblock):
 * host-apps catchen Mollie's eigen exceptions direct. Deze mapper bestaat voor
 * host-apps die uniforme catch-blocks willen over meerdere emeq/* SDKs heen
 * (snelstart-api gebruikt dezelfde subtype-namen).
 *
 * Gebruik:
 *
 *     try {
 *         Mollie::client()->payments->create([...]);
 *     } catch (\Mollie\Api\Exceptions\ApiException $e) {
 *         throw MollieExceptionMapper::map($e);
 *     }
 */
final class MollieExceptionMapper
{
    public static function map(Throwable $original): MollieException
    {
        return match (true) {
            $original instanceof MollieValidationException => ValidationException::fromMollie($original),
            $original instanceof MollieUnauthorizedException,
            $original instanceof MollieForbiddenException,
            $original instanceof MollieMissingAuthException,
            $original instanceof MollieInvalidAuthException => new AuthenticationException($original->getMessage(), $original->getCode(), $original),
            $original instanceof MollieNotFoundException    => new NotFoundException($original->getMessage(), $original->getCode(), $original),
            $original instanceof MollieRateLimitException   => new RateLimitException($original->getMessage(), $original->getCode(), $original),
            $original instanceof MollieServerException      => new ServerException($original->getMessage(), $original->getCode(), $original),
            $original instanceof MollieApiException         => new MollieException($original->getMessage(), $original->getCode(), $original),
            default                                         => new MollieException($original->getMessage(), $original->getCode(), $original),
        };
    }
}
