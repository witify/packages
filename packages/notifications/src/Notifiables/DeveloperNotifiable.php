<?php

namespace Witify\Notifications\Notifiables;

use Illuminate\Contracts\Translation\HasLocalePreference;
use Illuminate\Notifications\Notifiable;
use Illuminate\Notifications\Notification;

class DeveloperNotifiable implements HasLocalePreference
{
    use Notifiable;

    public string $first_name;

    public string $last_name;

    public string $full_name;

    public string $email;

    public string $locale;

    public function __construct()
    {
        $this->first_name = (string) config('notifications.developer.first_name', '');
        $this->last_name = (string) config('notifications.developer.last_name', '');
        $this->full_name = trim($this->first_name . ' ' . $this->last_name);
        $this->email = (string) config('notifications.developer.email', '');
        $this->locale = (string) config('notifications.developer.locale', 'fr');
    }

    public function routeNotificationForMail(Notification $notification): string
    {
        return $this->email;
    }

    public function preferredLocale(): string
    {
        return $this->locale;
    }
}
