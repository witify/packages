<?php

namespace Witify\Notifications\ValueObjects;

readonly class NotificationMessageData
{
    public function __construct(
        public string $notificationClass,
        public string $locale,
        public string $channel,
        public string $subject,
        public string $message,
        public bool $customized,
    ) {}
}
