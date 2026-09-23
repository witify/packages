<?php

namespace Witify\Devops\Tests;

use Spatie\Health\Checks\Checks\DatabaseCheck;
use Spatie\Health\Checks\Result;
use Witify\Devops\Notifications\CheckFailedNotification;

class CheckFailedNotificationTest extends TestCase
{
    public function test_the_mail_subject_names_the_application_url(): void
    {
        config()->set('app.url', 'https://app.example.com');
        config()->set('health.notifications.notifications', [CheckFailedNotification::class => ['mail']]);

        $result = Result::make()->failed('Could not connect to the database')->check(DatabaseCheck::new());

        $mail = (new CheckFailedNotification([$result]))->toMail();

        $this->assertSame('Health check failed on https://app.example.com', $mail->subject);
        $this->assertSame(['mail'], (new CheckFailedNotification([$result]))->via());
    }

    public function test_the_subject_is_translated(): void
    {
        config()->set('app.url', 'https://app.example.com');
        app()->setLocale('fr');

        $result = Result::make()->failed('Connexion impossible')->check(DatabaseCheck::new());

        $this->assertSame(
            'Vérification de santé en échec sur https://app.example.com',
            (new CheckFailedNotification([$result]))->toMail()->subject
        );
    }
}
