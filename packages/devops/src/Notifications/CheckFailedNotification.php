<?php

namespace Witify\Devops\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Spatie\Health\Notifications\CheckFailedNotification as SpatieCheckFailedNotification;

/**
 * The laravel-health notification with the URL of the application in the mail
 * subject, so the recipient sees which deployment fails before opening the mail.
 * Register it in `config/health.php` under `notifications.notifications`.
 */
class CheckFailedNotification extends SpatieCheckFailedNotification
{
    public function toMail(): MailMessage
    {
        return parent::toMail()->subject(
            (string) trans('devops::health.check_failed_mail_subject', ['url' => (string) config('app.url')])
        );
    }
}
