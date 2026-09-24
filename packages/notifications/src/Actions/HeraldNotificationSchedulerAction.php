<?php

namespace Witify\Notifications\Actions;

use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Cache;
use Witify\Notifications\Herald\HeraldNotifiable;
use Witify\Notifications\Herald\HeraldNotification;
use Witify\Notifications\Herald\HeraldOptions;
use Witify\Notifications\Herald\ScheduledNotification;
use Witify\Support\Action\Action;

class HeraldNotificationSchedulerAction implements Action
{
    const CACHE_PREFIX = 'herald_notification_schedule';

    /**
     * @param  class-string<HeraldNotification>  $heraldNotificationClass
     */
    public function __construct(private string $heraldNotificationClass, private string $timezone) {}

    public function handle(): void
    {
        /** @var HeraldOptions $heraldOptions */
        $heraldOptions = $this->heraldNotificationClass::herald();

        $heraldOptionsSchedule = $heraldOptions->getSchedule();

        if (! $heraldOptionsSchedule) {
            return;
        }

        $time = $heraldOptionsSchedule->getTime();

        $sendAfter = now()->timezone($this->timezone)->setTimeFromTimeString($time);

        // We are too early, the scheduled time is in the future, we will wait for the next run
        if ($sendAfter->isFuture()) {
            return;
        }

        // We are in the scheduled time window, we can send the notifications if we haven't already sent them today
        if ($this->alreadySentToday()) {
            return;
        }

        Cache::put($this->cacheKey(), true, now()->addDay());

        $items = $heraldOptionsSchedule->getItems();

        if ($items->isEmpty()) {
            return;
        }

        foreach ($items as $item) {
            $this->processNotificationItem($item);
        }
    }

    private function processNotificationItem(ScheduledNotification $scheduledNotification): void
    {
        $notification = $scheduledNotification->notification;

        $notifiables = $scheduledNotification->notifiables
            ->filter(function (HeraldNotifiable $notifiable) {
                return $notifiable->timezone === $this->timezone;
            })
            ->all();

        /**
         * @var Notification
         *
         * @phpstan-ignore varTag.nativeType
         */
        $notification = $notification;

        (new SendNotificationWithoutCrashAction($notifiables, $notification))->handle();
    }

    private function alreadySentToday(): bool
    {
        $key = $this->cacheKey();

        return Cache::get($key, false);
    }

    private function cacheKey(): string
    {
        $dateStr = now()->timezone($this->timezone)->toDateString();

        return self::CACHE_PREFIX . ':' . $this->heraldNotificationClass . ':' . $dateStr . ':' . $this->timezone;
    }
}
