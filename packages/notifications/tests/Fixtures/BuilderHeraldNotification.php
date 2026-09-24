<?php

namespace Witify\Notifications\Tests\Fixtures;

use Illuminate\Notifications\Notification;
use Witify\Notifications\Herald\HeraldNotifiable;
use Witify\Notifications\Herald\HeraldNotification;
use Witify\Notifications\Herald\HeraldNotificationTrait;
use Witify\Notifications\Herald\HeraldOptions;
use Witify\Notifications\Herald\NotificationMessageBuilder;

class BuilderHeraldNotification extends Notification implements HeraldNotification
{
    use HeraldNotificationTrait;

    public static function herald(): HeraldOptions
    {
        return HeraldOptions::make(self::class)
            ->title('Builder notification')
            ->description('Builder notification')
            ->customizable(true)
            ->variables(function (?HeraldNotification $notification, HeraldNotifiable $notifiable) {
                return [
                    'invoice' => [
                        'doc_number' => 'INV-1',
                    ],
                ];
            })
            ->mail(function () {
                return NotificationMessageBuilder::mail()
                    ->subject('Invoice :invoice.doc_number')
                    ->message('<p>Hello :first_name for :invoice.doc_number</p>');
            })
            ->database(function (?HeraldNotification $notification, HeraldNotifiable $notifiable, array $variables) {
                return NotificationMessageBuilder::database()
                    ->message('Database :invoice.doc_number for :first_name')
                    ->model($notifiable->user)
                    ->url('https://example.test/invoices/:invoice.doc_number')
                    ->path('invoices/:invoice.doc_number');
            })
            ->previews(fn () => []);
    }
}
