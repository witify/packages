<?php

namespace Witify\Notifications\Herald;

class NotificationPreview
{
    public function __construct(
        public HeraldNotification $notification,
        public string $title,
    ) {}
}
