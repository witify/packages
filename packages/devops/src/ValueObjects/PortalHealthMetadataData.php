<?php

namespace Witify\Devops\ValueObjects;

final class PortalHealthMetadataData
{
    public string $environment;

    public ?string $version;

    public ?int $sentryProjectId;

    /**
     * @var list<int>
     */
    public array $sentryProjectIds;

    public ?string $githubRepository;

    public HostingData $hosting;

    public PortalHealthBackupData $backup;

    /**
     * @param  list<int>  $sentryProjectIds
     */
    public function __construct(
        string $environment,
        ?string $version,
        ?int $sentryProjectId,
        array $sentryProjectIds,
        ?string $githubRepository,
        HostingData $hosting,
        PortalHealthBackupData $backup
    ) {
        $this->environment = $environment;
        $this->version = $version;
        $this->sentryProjectId = $sentryProjectId;
        $this->sentryProjectIds = $sentryProjectIds;
        $this->githubRepository = $githubRepository;
        $this->hosting = $hosting;
        $this->backup = $backup;
    }

    /**
     * The portal reads the application version under the `sprintify_version` key,
     * whatever the application is.
     *
     * @return array{environment: string, sprintify_version: ?string, sentry_project_id: ?int, sentry_project_ids: list<int>, github_repository: ?string, hosting: array{provider: ?string, environment_id: ?string, commit_sha: ?string}, backup: array{bucket: ?string, prefix: ?string}}
     */
    public function toArray(): array
    {
        return [
            'environment' => $this->environment,
            'sprintify_version' => $this->version,
            'sentry_project_id' => $this->sentryProjectId,
            'sentry_project_ids' => $this->sentryProjectIds,
            'github_repository' => $this->githubRepository,
            'hosting' => $this->hosting->toArray(),
            'backup' => $this->backup->toArray(),
        ];
    }
}
