<?php

declare(strict_types=1);

use Emeq\MollieApi\Facades\Mollie as MollieFacade;
use Illuminate\Foundation\AliasLoader;

it('registers the "Mollie" alias by default', function (): void {
    expect(AliasLoader::getInstance()->getAliases())
        ->toHaveKey('Mollie')
        ->and(AliasLoader::getInstance()->getAliases()['Mollie'])->toBe(MollieFacade::class);
});

it('registers a custom alias when config(mollie.facade_alias) is overridden', function (): void {
    config(['mollie.facade_alias' => 'EmeqMollie']);

    AliasLoader::getInstance()->setAliases([]);
    $provider = app()->getProvider(Emeq\MollieApi\MollieServiceProvider::class);
    $provider->packageBooted();

    expect(AliasLoader::getInstance()->getAliases())
        ->toHaveKey('EmeqMollie')
        ->and(AliasLoader::getInstance()->getAliases()['EmeqMollie'])->toBe(MollieFacade::class);
});

it('skips alias registration when config(mollie.facade_alias) is null', function (): void {
    config(['mollie.facade_alias' => null]);

    AliasLoader::getInstance()->setAliases([]);
    $provider = app()->getProvider(Emeq\MollieApi\MollieServiceProvider::class);
    $provider->packageBooted();

    expect(AliasLoader::getInstance()->getAliases())->not->toHaveKey('Mollie');
});

it('skips alias registration when config(mollie.facade_alias) is empty string', function (): void {
    config(['mollie.facade_alias' => '']);

    AliasLoader::getInstance()->setAliases([]);
    $provider = app()->getProvider(Emeq\MollieApi\MollieServiceProvider::class);
    $provider->packageBooted();

    expect(AliasLoader::getInstance()->getAliases())->not->toHaveKey('Mollie');
});
