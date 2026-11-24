<?php

namespace Witify\Devops\Actions;

use Illuminate\Contracts\Foundation\Application;
use Witify\Devops\ValueObjects\ApplicationInfoData;

class GetApplicationInfoAction
{
    private Application $application;

    private GetGitHubRepositoryAction $getGitHubRepositoryAction;

    private GetReleaseVersionAction $getReleaseVersionAction;

    private GetSentryProjectIdsAction $getSentryProjectIdsAction;

    private GetHostingAction $getHostingAction;

    public function __construct(
        Application $application,
        GetGitHubRepositoryAction $getGitHubRepositoryAction,
        GetReleaseVersionAction $getReleaseVersionAction,
        GetSentryProjectIdsAction $getSentryProjectIdsAction,
        GetHostingAction $getHostingAction
    ) {
        $this->application = $application;
        $this->getGitHubRepositoryAction = $getGitHubRepositoryAction;
        $this->getReleaseVersionAction = $getReleaseVersionAction;
        $this->getSentryProjectIdsAction = $getSentryProjectIdsAction;
        $this->getHostingAction = $getHostingAction;
    }

    public function handle(): ApplicationInfoData
    {
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
            $this->getSentryProjectIdsAction->handle(),
            $this->getHostingAction->handle(),
        );
    }
}
