<?php

declare(strict_types=1);

use Emeq\MollieApi\Idempotency\UuidV7IdempotencyKeyGenerator;
use Mollie\Api\Contracts\IdempotencyKeyGeneratorContract;
use Symfony\Component\Uid\Uuid;

it('implements the Mollie IdempotencyKeyGeneratorContract', function (): void {
    expect(new UuidV7IdempotencyKeyGenerator())->toBeInstanceOf(IdempotencyKeyGeneratorContract::class);
});

it('generates RFC 4122 UUID v7 strings', function (): void {
    $key = (new UuidV7IdempotencyKeyGenerator())->generate();

    expect($key)->toMatch('/^[0-9a-f]{8}-[0-9a-f]{4}-7[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i');
    expect(Uuid::isValid($key))->toBeTrue();
});

it('produces unique keys across calls', function (): void {
    $gen = new UuidV7IdempotencyKeyGenerator();

    $a = $gen->generate();
    $b = $gen->generate();

    expect($a)->not->toBe($b);
});

it('produces chronologically sortable keys (UUID v7 timestamp-prefix)', function (): void {
    $gen = new UuidV7IdempotencyKeyGenerator();

    $first = $gen->generate();
    usleep(2_000);
    $second = $gen->generate();

    expect(strcmp($first, $second))->toBeLessThan(0);
});
