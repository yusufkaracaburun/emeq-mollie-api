<?php

declare(strict_types=1);

namespace Emeq\MollieApi\Exceptions;

/**
 * Maps 5xx errors van mollie/mollie-api-php
 * (Mollie\Api\Exceptions\ServerException incl. ServiceUnavailableException 503).
 */
final class ServerException extends MollieException
{
}
