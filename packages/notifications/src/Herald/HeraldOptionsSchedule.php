<?php

namespace Witify\Notifications\Herald;

use Closure;
use Illuminate\Support\Collection;

class HeraldOptionsSchedule
{
    private string $time;

    private ?Closure $itemsClosure = null;

    // SETTERS

    /**
     * @param  Closure(): Collection<int,ScheduledNotification>  $closure
     */
    public function items(Closure $closure): self
    {
        $this->itemsClosure = $closure;

        return $this;
    }

    /**
     * Time hh:mm
     */
    public function at(string $time): self
    {
        $this->time = $time;

        return $this;
    }

    // GETTERS

    /**
     * @return Collection<int,ScheduledNotification>
     */
    public function getItems(): Collection
    {
        if (! $this->itemsClosure) {
            return collect();
        }

        return ($this->itemsClosure)();
    }

    public function getTime(): string
    {
        return $this->time;
    }
}
