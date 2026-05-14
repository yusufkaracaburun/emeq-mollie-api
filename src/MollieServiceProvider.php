<?php

declare(strict_types=1);

namespace Emeq\MollieApi;

use Emeq\MollieApi\Contracts\MollieCredentialResolver;
use Emeq\MollieApi\Exceptions\MissingCredentialResolverException;
use Emeq\MollieApi\Facades\Mollie as MollieFacade;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Foundation\AliasLoader;
use Mollie\Api\MollieApiClient;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class MollieServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('mollie-api')
            ->hasConfigFile('mollie');
    }

    public function packageRegistered(): void
    {
        // The credential resolver is intentionally NOT bound here — the host
        // app must provide its own. Resolving Mollie::class without a
        // resolver throws a helpful exception rather than Laravel's generic
        // BindingResolutionException.
        $this->app->singleton(Mollie::class, function (Application $app): Mollie {
            if ( ! $app->bound(MollieCredentialResolver::class)) {
                throw MissingCredentialResolverException::notBound();
            }

            return new Mollie(
                resolver: $app->make(MollieCredentialResolver::class),
                config: $app->make('config'),
                container: $app,
            );
        });

        // MollieApiClient is bound NON-singleton on purpose: every resolve
        // produces a fresh client wired with the current tenant's credentials.
        // Use this binding when you want to inject MollieApiClient directly
        // into a class constructor and not go through the facade.
        $this->app->bind(
            MollieApiClient::class,
            fn (Application $app): MollieApiClient => $app->make(Mollie::class)->client(),
        );
    }

    public function packageBooted(): void
    {
        $this->registerFacadeAlias();
    }

    /**
     * Registreer de class-alias voor de Mollie-facade via AliasLoader.
     *
     * Dynamisch (ipv via composer.json extra.laravel.aliases) zodat host-apps
     * de naam via config('mollie.facade_alias') kunnen wijzigen of op null
     * kunnen zetten om alias-collisions met mollie/laravel-mollie te vermijden.
     */
    private function registerFacadeAlias(): void
    {
        $alias = $this->app->make('config')->get('mollie.facade_alias');

        if (null === $alias || '' === $alias) {
            return;
        }

        AliasLoader::getInstance()->alias($alias, MollieFacade::class);
    }
}
