<?php

namespace Witify\Devops\Actions;

class GetSentryProjectIdsAction
{
    /**
     * Ids of the Sentry projects of the application, without duplicates: the one of
     * `devops.sentry_dsn` first, then those of `devops.sentry_additional_dsns`.
     *
     * @return list<int>
     */
    public function handle(): array
    {
        $additionalDsns = config('devops.sentry_additional_dsns', []);

        if (! is_array($additionalDsns)) {
            $additionalDsns = [$additionalDsns];
        }

        $projectIds = [];

        foreach (array_merge([config('devops.sentry_dsn')], array_values($additionalDsns)) as $dsn) {
            $projectId = (new ParseSentryProjectIdAction(is_string($dsn) ? $dsn : null))->handle();

            if ($projectId !== null && ! in_array($projectId, $projectIds, true)) {
                $projectIds[] = $projectId;
            }
        }

        return $projectIds;
    }
}
