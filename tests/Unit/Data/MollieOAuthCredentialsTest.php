<?php

declare(strict_types=1);

use Emeq\MollieApi\Data\MollieOAuthCredentials;

it('accepts a valid access_ prefixed token with optional expiresAt', function (): void {
    $creds = new MollieOAuthCredentials('access_xyz789', expiresAt: 1740000000);

    expect($creds->accessToken)->toBe('access_xyz789')
        ->and($creds->expiresAt)->toBe(1740000000)
        ->and($creds->fingerprint())->toBe(substr(hash('sha256', 'access_xyz789'), 0, 12));
});

it('accepts an access_ token without expiresAt', function (): void {
    $creds = new MollieOAuthCredentials('access_no_exp');
    expect($creds->expiresAt)->toBeNull();
});

it('rejects an empty accessToken', function (): void {
    new MollieOAuthCredentials('');
})->throws(InvalidArgumentException::class, 'may not be empty');

it('rejects a whitespace-only accessToken', function (): void {
    new MollieOAuthCredentials('   ');
})->throws(InvalidArgumentException::class, 'may not be empty');

it('rejects a token without access_ prefix', function (): void {
    new MollieOAuthCredentials('test_xxx');
})->throws(InvalidArgumentException::class, 'must start with "access_"');

it('builds from array via fromArray', function (): void {
    $creds = MollieOAuthCredentials::fromArray(['accessToken' => 'access_x', 'expiresAt' => 999]);
    expect($creds)->toBeInstanceOf(MollieOAuthCredentials::class)
        ->and($creds->accessToken)->toBe('access_x')
        ->and($creds->expiresAt)->toBe(999);
});
