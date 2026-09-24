<?php

namespace Witify\Notifications\Actions;

use Illuminate\Support\Collection;
use Witify\Notifications\Herald\Herald;
use Witify\Notifications\ValueObjects\HeraldNotificationData;
use Witify\Support\Action\Action;

class BuildHeraldNotificationIndexAction implements Action
{
    /**
     * @return Collection<int, HeraldNotificationData>
     */
    public function handle(): Collection
    {
        return collect(Herald::notifications())
            ->map(fn (string $class, string $key): HeraldNotificationData => HeraldNotificationData::fromClass($key, $class))
            ->values();
    }
}
