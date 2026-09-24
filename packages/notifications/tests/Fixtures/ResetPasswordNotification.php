<?php

namespace Witify\Notifications\Tests\Fixtures;

use Illuminate\Notifications\Notification;
use Witify\Notifications\Herald\HeraldNotifiable;
use Witify\Notifications\Herald\HeraldNotification;
use Witify\Notifications\Herald\HeraldNotificationTrait;
use Witify\Notifications\Herald\HeraldOptions;
use Witify\Notifications\Herald\NotificationMessageBuilder;
use Witify\Notifications\Herald\NotificationPreview;

/**
 * A system notification: mail only, neither toggleable nor customizable.
 */
class ResetPasswordNotification extends Notification implements HeraldNotification
{
    use HeraldNotificationTrait;

    public function __construct(public string $token = 'token') {}

    public static function herald(): HeraldOptions
    {
        return HeraldOptions::make(self::class)
            ->title('Reset password')
            ->description('Sent when a user asks to reset the password.')
            ->group('System')
            ->toggleable(false)
            ->customizable(false)
            ->variables(fn (?HeraldNotification $notification, HeraldNotifiable $notifiable): array => [
                'url' => 'https://app.example.test/reset/' . ($notification instanceof self ? $notification->token : 'preview'),
            ])
            ->mail(fn () => NotificationMessageBuilder::mail()
                ->subject('Reset your password')
                ->message('<p>Hello :first_name, <a href=":url">reset your password</a>.</p>'))
            ->previews(fn (): array => [
                new NotificationPreview(new self('preview-token'), 'Reset password'),
            ]);
    }
}
