<?php

namespace Witify\Devops\ValueObjects;

use DateTimeInterface;

final class HealthSummaryData
{
    public ?DateTimeInterface $checkedAt;

    /** @var array<string, int> */
    public array $countsByStatus;

    /**
     * @param  array<string, int>  $countsByStatus
     */
    public function __construct(?DateTimeInterface $checkedAt, array $countsByStatus)
    {
        $this->checkedAt = $checkedAt;
        $this->countsByStatus = $countsByStatus;
    }

    public function total(): int
    {
        return array_sum($this->countsByStatus);
    }

    public function count(string $status): int
    {
        return $this->countsByStatus[$status] ?? 0;
    }

    public function hasResults(): bool
    {
        return $this->checkedAt !== null;
    }
}
