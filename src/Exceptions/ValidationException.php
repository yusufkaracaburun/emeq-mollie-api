<?php

declare(strict_types=1);

namespace Emeq\MollieApi\Exceptions;

use Mollie\Api\Exceptions\ValidationException as MollieValidationException;
use Throwable;

/**
 * Maps 422 unprocessable-entity errors van mollie/mollie-api-php.
 *
 * Geeft toegang tot het field-name uit Mollie's response (matched
 * Mollie\Api\Exceptions\ValidationException::getField()).
 */
final class ValidationException extends MollieException
{
    public function __construct(
        string $message,
        private readonly ?string $field,
        int $code = 0,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }

    public static function fromMollie(MollieValidationException $original): self
    {
        return new self(
            message: $original->getMessage(),
            field: $original->getField(),
            code: $original->getCode(),
            previous: $original,
        );
    }

    public function getField(): ?string
    {
        return $this->field;
    }
}
