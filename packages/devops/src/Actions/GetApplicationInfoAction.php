<?php

namespace Witify\Devops\Actions;

use Illuminate\Contracts\Foundation\Application;
use Witify\Devops\ValueObjects\ApplicationInfoData;

class GetApplicationInfoAction
{
    private Application $application;

    private GetGitHubRepositoryAction $getGitHubRepositoryAction;

    private GetReleaseVersionAction $getReleaseVersionAction;

    public function __construct(
        Application $application,
        GetGitHubRepositoryAction $getGitHubRepositoryAction,
        GetReleaseVersionAction $getReleaseVersionAction
    ) {
        $this->application = $application;
        $this->getGitHubRepositoryAction = $getGitHubRepositoryAction;
        $this->getReleaseVersionAction = $getReleaseVersionAction;
    }

    public function handle(): ApplicationInfoData
    {
        $sentryDsn = config('devops.sentry_dsn');

        return new ApplicationInfoData(
            (string) config('app.name'),
            (string) config('app.env'),
            $this->getReleaseVersionAction->handle(),
            PHP_VERSION,
            $this->application->version(),
            (bool) config('app.debug'),
            $this->application->isDownForMaintenance(),
            $this->application->configurationIsCached(),
            $this->application->routesAreCached(),
            $this->getGitHubRepositoryAction->handle(),
            (new ParseSentryProjectIdAction(is_string($sentryDsn) ? $sentryDsn : null))->handle(),
        );
    }
}
