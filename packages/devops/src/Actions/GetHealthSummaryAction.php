<?php

namespace Witify\Devops\Actions;

use Spatie\Health\ResultStores\ResultStore;
use Spatie\Health\ResultStores\StoredCheckResults\StoredCheckResult;
use Witify\Devops\ValueObjects\HealthSummaryData;

class GetHealthSummaryAction
{
    private ResultStore $resultStore;

    public function __construct(ResultStore $resultStore)
    {
        $this->resultStore = $resultStore;
    }

    public function handle(): HealthSummaryData
    {
        $latestResults = $this->resultStore->latestResults();

        if ($latestResults === null) {
            return new HealthSummaryData(null, []);
        }

        $countsByStatus = $latestResults->storedCheckResults
            ->countBy(fn (StoredCheckResult $result): string => $result->status)
            ->all();

        return new HealthSummaryData($latestResults->finishedAt, $countsByStatus);
    }
}
