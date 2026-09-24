<?php

namespace Witify\Notifications\Actions;

use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Notification as FacadesNotification;
use Throwable;
use Witify\Support\Action\Action;

class SendNotificationWithoutCrashAction implements Action
{
    public function __construct(
        private mixed $notifiables,
        private Notification $notification,
    ) {}

    public function handle(): void
    {
        try {
            FacadesNotification::send($this->notifiables, $this->notification);
        } catch (Throwable $e) {
            report($e);
        }
    }
}
