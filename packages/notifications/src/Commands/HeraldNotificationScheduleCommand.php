<?php

namespace Witify\Notifications\Commands;

use Illuminate\Console\Command;
use Witify\Notifications\Actions\HeraldNotificationSchedulerAction;
use Witify\Notifications\Herald\Herald;

class HeraldNotificationScheduleCommand extends Command
{
    protected $signature = 'herald:notification-schedule';

    protected $description = 'Schedule Herald notifications';

    public function handle(): void
    {
        $timezones = Herald::userModel()::query()->select('timezone')->distinct()->pluck('timezone')->unique()->filter();

        foreach ($timezones as $timezone) {
            foreach (Herald::notifications() as $heraldNotificationClass) {
                (new HeraldNotificationSchedulerAction($heraldNotificationClass, (string) $timezone))->handle();
            }
        }
    }
}
