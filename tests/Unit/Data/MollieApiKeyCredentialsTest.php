<?php

declare(strict_types=1);

use Emeq\MollieApi\Data\MollieApiKeyCredentials;

it('accepts a valid test_ prefixed key', function (): void {
    $creds = new MollieApiKeyCredentials('test_abc123');

    expect($creds->apiKey)->toBe('test_abc123')
        ->and($creds->isTestMode())->toBeTrue()
        ->and($creds->fingerprint())->toBe(substr(hash('sha256', 'test_abc123'), 0, 12));
});

it('accepts a valid live_ prefixed key', function (): void {
    $creds = new MollieApiKeyCredentials('live_prod456');

    expect($creds->apiKey)->toBe('live_prod456')
        ->and($creds->isTestMode())->toBeFalse();
});

it('rejects an empty apiKey', function (): void {
    new MollieApiKeyCredentials('');
})->throws(InvalidArgumentException::class, 'may not be empty');

it('rejects a whitespace-only apiKey', function (): void {
    new MollieApiKeyCredentials('   ');
})->throws(InvalidArgumentException::class, 'may not be empty');

it('rejects an apiKey without test_ or live_ prefix', function (): void {
    new MollieApiKeyCredentials('bogus_xxx');
})->throws(InvalidArgumentException::class, 'must start with "test_" or "live_"');

it('builds from array via fromArray', function (): void {
    $creds = MollieApiKeyCredentials::fromArray(['apiKey' => 'test_x']);
    expect($creds)->toBeInstanceOf(MollieApiKeyCredentials::class)
        ->and($creds->apiKey)->toBe('test_x');
});
