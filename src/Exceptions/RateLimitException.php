<?php

declare(strict_types=1);

namespace Emeq\MollieApi\Exceptions;

/**
 * Maps 429 errors van mollie/mollie-api-php (Mollie\Api\Exceptions\TooManyRequestsException).
 */
final class RateLimitException extends MollieException
{
}
