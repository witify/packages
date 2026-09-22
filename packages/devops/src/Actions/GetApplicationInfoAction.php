<?php

namespace Witify\Devops\Actions;

use Illuminate\Contracts\Foundation\Application;
use Witify\Devops\ValueObjects\ApplicationInfoData;

class GetApplicationInfoAction
{
    private Application $application;

    private GetGitHubRepositoryAction $getGitHubRepositoryAction;

    public function __construct(Application $application, GetGitHubRepositoryAction $getGitHubRepositoryAction)
    {
        $this->application = $application;
        $this->getGitHubRepositoryAction = $getGitHubRepositoryAction;
    }

    public function handle(): ApplicationInfoData
    {
        $version = config('devops.version');
        $sentryDsn = config('devops.sentry_dsn');

        return new ApplicationInfoData(
            (string) config('app.name'),
            (string) config('app.env'),
            is_string($version) && $version !== '' ? $version : null,
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
