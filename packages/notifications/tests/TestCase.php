<?php

namespace Witify\Notifications\Tests;

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Orchestra\Testbench\TestCase as TestbenchTestCase;
use Spatie\QueryBuilder\QueryBuilderServiceProvider;
use Witify\Notifications\Herald\Herald;
use Witify\Notifications\NotificationsServiceProvider;
use Witify\Notifications\Tests\Fixtures\ResetPasswordNotification;
use Witify\Notifications\Tests\Fixtures\User;
use Witify\Support\SupportServiceProvider;

abstract class TestCase extends TestbenchTestCase
{
    use RefreshDatabase;

    /**
     * @param  Application  $app
     * @return list<class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [QueryBuilderServiceProvider::class, SupportServiceProvider::class, NotificationsServiceProvider::class];
    }

    /**
     * @param  Application  $app
     */
    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('cache.default', 'array');
        $app['config']->set('session.driver', 'array');
        $app['config']->set('app.name', 'Client Example');
        $app['config']->set('app.locale', 'en');
        $app['config']->set('app.locales', ['en' => 'English', 'fr' => 'Français']);
        $app['config']->set('auth.providers.users.model', User::class);
        $app['config']->set('notifications.timezone', 'America/Toronto');
        $app['config']->set('mail.default', 'array');
    }

    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/database/migrations');
    }

    protected function setUp(): void
    {
        parent::setUp();

        Herald::forget();
        Herald::register(['reset_password' => ResetPasswordNotification::class]);
    }

    protected function tearDown(): void
    {
        Herald::forget();

        parent::tearDown();
    }
}
