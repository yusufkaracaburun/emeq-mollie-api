<?php

declare(strict_types=1);

namespace Emeq\MollieApi\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Emeq\MollieApi\Mollie
 *
 * @method static \Emeq\MollieApi\Data\MollieApiKeyCredentials|\Emeq\MollieApi\Data\MollieOAuthCredentials credentials()
 * @method static \Mollie\Api\MollieApiClient client()
 */
class Mollie extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Emeq\MollieApi\Mollie::class;
    }
}
