<?php

declare(strict_types=1);

namespace Emeq\MollieApi\Tests;

use Emeq\MollieApi\MollieServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected function getEnvironmentSetUp($app): void
    {
        config()->set('database.default', 'testing');
    }

    protected function getPackageProviders($app): array
    {
        return [
            MollieServiceProvider::class,
        ];
    }
}
