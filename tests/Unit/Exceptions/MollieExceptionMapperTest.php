<?php

declare(strict_types=1);

use Emeq\MollieApi\Exceptions\AuthenticationException;
use Emeq\MollieApi\Exceptions\MollieException;
use Emeq\MollieApi\Exceptions\MollieExceptionMapper;
use Emeq\MollieApi\Exceptions\NotFoundException;
use Emeq\MollieApi\Exceptions\RateLimitException;
use Emeq\MollieApi\Exceptions\ServerException;
use Emeq\MollieApi\Exceptions\ValidationException;
use Mollie\Api\Exceptions\ApiException as MollieApiException;
use Mollie\Api\Exceptions\ForbiddenException as MollieForbiddenException;
use Mollie\Api\Exceptions\InvalidAuthenticationException as MollieInvalidAuthException;
use Mollie\Api\Exceptions\MissingAuthenticationException as MollieMissingAuthException;
use Mollie\Api\Exceptions\NotFoundException as MollieNotFoundException;
use Mollie\Api\Exceptions\ServerException as MollieServerException;
use Mollie\Api\Exceptions\TooManyRequestsException as MollieRateLimitException;
use Mollie\Api\Exceptions\UnauthorizedException as MollieUnauthorizedException;
use Mollie\Api\Exceptions\ValidationException as MollieValidationException;

/**
 * Mollie's ApiException-subtypes nemen een Response-object in hun constructor,
 * dat zelf weer een PSR-7-request en een PendingRequest nodig heeft. Voor een
 * pure mapper-unit-test bypassen we de constructor via Reflection — we testen
 * de mapper, niet Mollie's hydrate-pipeline (die test wordt al gedekt in
 * ErrorMappingTest.php met MollieApiClient::fake()).
 *
 * @template T of Throwable
 *
 * @param  class-string<T>  $class
 * @return T
 */
function makeMollieException(string $class, string $message = 'mock', int $code = 0): Throwable
{
    $instance = (new ReflectionClass($class))->newInstanceWithoutConstructor();

    $base = new ReflectionClass(Exception::class);
    $base->getProperty('message')->setValue($instance, $message);
    $base->getProperty('code')->setValue($instance, $code);

    return $instance;
}

it('maps Mollie ValidationException + preserves field', function (): void {
    $original  = makeMollieException(MollieValidationException::class, 'amount is required', 422);
    $fieldProp = (new ReflectionClass(MollieValidationException::class))->getProperty('field');
    $fieldProp->setValue($original, 'amount.value');

    $mapped = MollieExceptionMapper::map($original);

    expect($mapped)->toBeInstanceOf(ValidationException::class)
        ->and($mapped->getField())->toBe('amount.value')
        ->and($mapped->getMessage())->toBe('amount is required')
        ->and($mapped->getPrevious())->toBe($original);
});

it('maps 401/403/missing/invalid-auth to AuthenticationException', function (string $class): void {
    $original = makeMollieException($class, 'not allowed', 401);

    expect(MollieExceptionMapper::map($original))
        ->toBeInstanceOf(AuthenticationException::class);
})->with([
    [MollieUnauthorizedException::class],
    [MollieForbiddenException::class],
    [MollieMissingAuthException::class],
    [MollieInvalidAuthException::class],
]);

it('maps 404 to NotFoundException', function (): void {
    $original = makeMollieException(MollieNotFoundException::class, 'missing', 404);

    expect(MollieExceptionMapper::map($original))->toBeInstanceOf(NotFoundException::class);
});

it('maps 429 to RateLimitException', function (): void {
    $original = makeMollieException(MollieRateLimitException::class, 'slow down', 429);

    expect(MollieExceptionMapper::map($original))->toBeInstanceOf(RateLimitException::class);
});

it('maps 5xx to ServerException', function (): void {
    $original = makeMollieException(MollieServerException::class, 'boom', 500);

    expect(MollieExceptionMapper::map($original))->toBeInstanceOf(ServerException::class);
});

it('falls back to MollieException base for unknown Mollie ApiException subtypes', function (): void {
    $original = makeMollieException(MollieApiException::class, 'something else', 418);

    $mapped = MollieExceptionMapper::map($original);

    expect($mapped)->toBeInstanceOf(MollieException::class)
        ->and($mapped)->not->toBeInstanceOf(ValidationException::class)
        ->and($mapped->getPrevious())->toBe($original);
});

it('falls back to MollieException base for arbitrary Throwables', function (): void {
    $original = new RuntimeException('network died');

    $mapped = MollieExceptionMapper::map($original);

    expect($mapped)->toBeInstanceOf(MollieException::class)
        ->and($mapped->getPrevious())->toBe($original);
});
