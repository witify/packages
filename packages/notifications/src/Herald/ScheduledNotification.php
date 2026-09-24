<?php

namespace Witify\Notifications\Herald;

use Illuminate\Support\Collection;

class ScheduledNotification
{
    /**
     * @param  Collection<int, HeraldNotifiable>  $notifiables
     */
    public function __construct(
        public HeraldNotification $notification,
        public Collection $notifiables,
    ) {}
}
