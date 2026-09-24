<?php

namespace Witify\Notifications\Herald;

/**
 * The user model of the application, as Herald sees it: a notifiable with
 * per-notification channel settings. The settings are the list the
 * notification settings screen edits: [{key, channels: {mail, database}}].
 */
interface HeraldUser extends IsHeraldNotifiable
{
    /**
     * @return array<int, array{key: string, channels: array<string, bool>}>
     */
    public function getNotificationSettings(): array;

    /**
     * @param  array<int, array{key: string, channels: array<string, bool>}>  $settings
     */
    public function setNotificationSettings(array $settings): void;
}
