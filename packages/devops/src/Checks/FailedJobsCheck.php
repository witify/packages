<?php

namespace Witify\Devops\Checks;

use Spatie\Health\Checks\Result;
use Spatie\Health\ResultStores\StoredCheckResults\StoredCheckResult;
use Witify\Devops\Models\FailedJob;
use Witify\Devops\ValueObjects\LocalizedTextData;

class FailedJobsCheck extends ClientFacingCheck
{
    public const LABEL_KEY = 'devops::health.failed_jobs.label';

    public const MESSAGE_KEY = 'devops::health.failed_jobs.message';

    public const META_FAILED_JOBS_COUNT = 'failed_jobs_count';

    public function clientLabel(): LocalizedTextData
    {
        return LocalizedTextData::fromTranslation(self::LABEL_KEY);
    }

    public function clientMessage(StoredCheckResult $result): LocalizedTextData
    {
        $failedJobCount = (int) ($result->meta[self::META_FAILED_JOBS_COUNT] ?? 0);

        return $this->message($failedJobCount);
    }

    public function run(): Result
    {
        $failedJobCount = FailedJob::query()->count();

        if ($failedJobCount === 0) {
            return Result::make()->ok();
        }

        return Result::make()
            ->failed($this->message($failedJobCount)->fr)
            ->meta([self::META_FAILED_JOBS_COUNT => $failedJobCount]);
    }

    private function message(int $failedJobCount): LocalizedTextData
    {
        return LocalizedTextData::fromTranslationChoice(
            self::MESSAGE_KEY,
            $failedJobCount,
            ['count' => $failedJobCount],
        );
    }
}
