<?php

declare(strict_types=1);

use Emeq\MollieApi\Contracts\MollieCredentialResolver;
use Emeq\MollieApi\Mollie;
use Emeq\MollieApi\Testing\FakeMollieCredentialResolver;
use Mollie\Api\MollieApiClient;

it('binds Mollie::class as a singleton', function (): void {
    app()->bind(
        MollieCredentialResolver::class,
        fn () => FakeMollieCredentialResolver::withApiKey('test_singletonAAAAAAAAAAAAAAAAAAAAAA'),
    );

    $first  = app(Mollie::class);
    $second = app(Mollie::class);

    expect($first)->toBe($second);
});

it('binds MollieApiClient::class as a non-singleton (fresh client per resolve)', function (): void {
    app()->bind(
        MollieCredentialResolver::class,
        fn () => FakeMollieCredentialResolver::withApiKey('test_percallAAAAAAAAAAAAAAAAAAAAAAAA'),
    );

    $a = app(MollieApiClient::class);
    $b = app(MollieApiClient::class);
    $c = app(MollieApiClient::class);

    expect($a)->toBeInstanceOf(MollieApiClient::class)
        ->and($b)->toBeInstanceOf(MollieApiClient::class)
        ->and($c)->toBeInstanceOf(MollieApiClient::class)
        ->and($a)->not->toBe($b)
        ->and($b)->not->toBe($c)
        ->and($a)->not->toBe($c);
});

it('does not pre-bind MollieCredentialResolver — host app must bind', function (): void {
    expect(app()->bound(MollieCredentialResolver::class))->toBeFalse();

    app()->bind(
        MollieCredentialResolver::class,
        fn () => FakeMollieCredentialResolver::withApiKey('test_postbindAAAAAAAAAAAAAAAAAAAAAAA'),
    );

    expect(app()->bound(MollieCredentialResolver::class))->toBeTrue();
});
