<?php

namespace Witify\Notifications\Actions;

use Witify\Notifications\Herald\HeraldUser;
use Witify\Notifications\ValueObjects\UserNotificationSettingsData;
use Witify\Support\Action\Action;

class SetUserNotificationsAction implements Action
{
    public function __construct(
        private HeraldUser $user,
        private UserNotificationSettingsData $notificationSettings,
    ) {}

    public function handle(): HeraldUser
    {
        $this->user->setNotificationSettings($this->notificationSettings->toArray());

        return $this->user;
    }
}
