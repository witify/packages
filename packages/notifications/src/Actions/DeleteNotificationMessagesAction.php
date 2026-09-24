<?php

namespace Witify\Notifications\Actions;

use Witify\Notifications\Models\NotificationMessage;
use Witify\Support\Action\Action;

class DeleteNotificationMessagesAction implements Action
{
    public function __construct(
        private string $notificationClass,
        private ?string $channel = null,
    ) {}

    public function handle(): void
    {
        NotificationMessage::query()
            ->where('notification_class', $this->notificationClass)
            ->when(
                $this->channel,
                fn ($query, string $channel) => $query->where('channel', $channel),
            )
            ->delete();
    }
}
