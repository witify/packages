<?php

namespace Witify\Notifications;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Witify\Notifications\Commands\HeraldNotificationScheduleCommand;
use Witify\Notifications\Models\Notification;
use Witify\Notifications\Policies\NotificationPolicy;

class NotificationsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/notifications.php', 'notifications');
    }

    public function boot(): void
    {
        $this->loadTranslationsFrom(__DIR__ . '/../lang', 'notifications');
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        Gate::policy(Notification::class, NotificationPolicy::class);

        if (config('notifications.routes.enabled', true)) {
            $this->loadRoutesFrom(__DIR__ . '/../routes/api.php');
        }

        if ($this->app->runningInConsole()) {
            $this->commands([HeraldNotificationScheduleCommand::class]);

            $this->publishes([
                __DIR__ . '/../config/notifications.php' => config_path('notifications.php'),
            ], 'notifications-config');

            $this->publishes([
                __DIR__ . '/../lang' => $this->app->langPath() . '/vendor/notifications',
            ], 'notifications-translations');
        }
    }
}
