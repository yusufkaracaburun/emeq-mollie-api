<?php

declare(strict_types=1);

use Emeq\MollieApi\Data\MollieApiKeyCredentials;
use Emeq\MollieApi\Data\MollieOAuthCredentials;

it('returns first 12 chars of sha256(secret) for both credential types', function (): void {
    $apiKey = new MollieApiKeyCredentials('test_abc123');
    $oauth  = new MollieOAuthCredentials('access_xyz789');

    expect($apiKey->fingerprint())
        ->toBe(substr(hash('sha256', 'test_abc123'), 0, 12))
        ->toHaveLength(12)
        ->and($apiKey->fingerprint())->not->toContain('test_abc123')
        ->and($apiKey->fingerprint())->not->toContain('abc123');

    expect($oauth->fingerprint())
        ->toBe(substr(hash('sha256', 'access_xyz789'), 0, 12))
        ->toHaveLength(12)
        ->and($oauth->fingerprint())->not->toContain('access_xyz789')
        ->and($oauth->fingerprint())->not->toContain('xyz789');

    expect($apiKey->fingerprint())->not->toBe($oauth->fingerprint());
});

it('produces deterministic fingerprints across calls', function (): void {
    $creds = new MollieApiKeyCredentials('test_deterministic');
    expect($creds->fingerprint())->toBe($creds->fingerprint());
});
