<?php

namespace Witify\Notifications\Actions;

use Witify\Notifications\Herald\Herald;
use Witify\Notifications\Herald\HeraldNotification;
use Witify\Notifications\ValueObjects\HeraldNotificationDetailsData;
use Witify\Support\Action\Action;

class BuildHeraldNotificationDetailsAction implements Action
{
    public function __construct(
        private string $notificationClass,
    ) {}

    public function handle(): HeraldNotificationDetailsData
    {
        $key = Herald::keyOf($this->notificationClass);

        abort_if($key === null, 404);

        /** @var class-string<HeraldNotification> $class */
        $class = $this->notificationClass;

        return HeraldNotificationDetailsData::fromClass($key, $class);
    }
}
