<?php

namespace Witify\Notifications\ValueObjects;

readonly class NotificationMessageBatchData
{
    /**
     * @param  array<int, NotificationMessageData>  $messages
     */
    public function __construct(
        public array $messages,
    ) {}
}
