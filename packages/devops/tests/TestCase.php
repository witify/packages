<?php

namespace Witify\Devops\Tests;

use Illuminate\Foundation\Application;
use Orchestra\Testbench\TestCase as TestbenchTestCase;
use Spatie\Health\HealthServiceProvider;
use Spatie\Health\ResultStores\CacheHealthResultStore;
use Witify\Devops\DevopsServiceProvider;

abstract class TestCase extends TestbenchTestCase
{
    /**
     * @param  Application  $app
     * @return list<class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [
            HealthServiceProvider::class,
            DevopsServiceProvider::class,
        ];
    }

    /**
     * @param  Application  $app
     */
    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('cache.default', 'array');
        $app['config']->set('health.result_stores', [
            CacheHealthResultStore::class => ['store' => 'array'],
        ]);
        $app['config']->set('health.notifications.enabled', false);
    }
}
