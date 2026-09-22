<?php

namespace Witify\Devops;

use Illuminate\Support\ServiceProvider;

class DevopsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/devops.php', 'devops');
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/../routes/api.php');
        $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');
        $this->loadTranslationsFrom(__DIR__ . '/../lang', 'devops');
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'devops');

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/devops.php' => config_path('devops.php'),
            ], 'devops-config');

            $this->publishes([
                __DIR__ . '/../lang' => $this->app->langPath() . '/vendor/devops',
            ], 'devops-translations');

            $this->publishes([
                __DIR__ . '/../resources/views' => resource_path('views/vendor/devops'),
            ], 'devops-views');
        }
    }
}
