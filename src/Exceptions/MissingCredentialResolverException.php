<?php

declare(strict_types=1);

namespace Emeq\MollieApi\Exceptions;

use Emeq\MollieApi\Contracts\MollieCredentialResolver;

/**
 * Thrown when the package is used but the host app never bound a
 * MollieCredentialResolver in the container.
 *
 * The package is intentionally tenant-agnostic — it does not assume any
 * particular multi-tenancy layer (stancl/tenancy, spatie/multitenancy,
 * Emeq Hub Connection model, custom), so the host app MUST tell it how
 * to fetch credentials.
 */
final class MissingCredentialResolverException extends MollieException
{
    public static function notBound(): self
    {
        return new self(
            sprintf(
                'No %s binding found in the container. Bind your resolver in a ServiceProvider, e.g.: ' .
                "\$this->app->bind(%s::class, YourTenantResolver::class);",
                MollieCredentialResolver::class,
                MollieCredentialResolver::class,
            ),
        );
    }
}
