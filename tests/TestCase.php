<?php

declare(strict_types=1);

namespace Tests;

use Orchestra\Testbench\TestCase as OrchestraTestCase;
use Revolution\Feedable\FeedableServiceProvider;
use Revolution\Salvager\Providers\SalvagerServiceProvider;

abstract class TestCase extends OrchestraTestCase
{
    protected function defineEnvironment($app): void
    {
        $app['env'] = 'testing';
    }

    protected function getPackageProviders($app): array
    {
        return [
            FeedableServiceProvider::class,
            SalvagerServiceProvider::class,
        ];
    }
}
