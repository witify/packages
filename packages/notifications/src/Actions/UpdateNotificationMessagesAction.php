<?php

namespace Witify\Notifications\Actions;

use Witify\Notifications\Models\NotificationMessage;
use Witify\Notifications\ValueObjects\NotificationMessageBatchData;
use Witify\Notifications\ValueObjects\NotificationMessageData;
use Witify\Support\Action\Action;

class UpdateNotificationMessagesAction implements Action
{
    public function __construct(
        private NotificationMessageBatchData $notificationMessages,
    ) {}

    public function handle(): void
    {
        foreach ($this->notificationMessages->messages as $message) {
            $attributes = $this->attributes($message);

            if (! $message->customized) {
                NotificationMessage::query()
                    ->where($attributes)
                    ->delete();

                continue;
            }

            NotificationMessage::query()->updateOrCreate($attributes, [
                'subject' => $message->subject,
                'message' => $message->message,
            ]);
        }
    }

    /**
     * @return array{notification_class: string, locale: string, channel: string}
     */
    private function attributes(NotificationMessageData $message): array
    {
        return [
            'notification_class' => $message->notificationClass,
            'locale' => $message->locale,
            'channel' => $message->channel,
        ];
    }
}
