<?php

namespace Witify\Devops\ValueObjects;

final class PortalHealthMetadataData
{
    public string $environment;

    public ?string $version;

    public ?int $sentryProjectId;

    public ?string $githubRepository;

    public PortalHealthBackupData $backup;

    public function __construct(
        string $environment,
        ?string $version,
        ?int $sentryProjectId,
        ?string $githubRepository,
        PortalHealthBackupData $backup
    ) {
        $this->environment = $environment;
        $this->version = $version;
        $this->sentryProjectId = $sentryProjectId;
        $this->githubRepository = $githubRepository;
        $this->backup = $backup;
    }

    /**
     * The portal reads the application version under the `sprintify_version` key,
     * whatever the application is.
     *
     * @return array{environment: string, sprintify_version: ?string, sentry_project_id: ?int, github_repository: ?string, backup: array{bucket: ?string, prefix: ?string}}
     */
    public function toArray(): array
    {
        return [
            'environment' => $this->environment,
            'sprintify_version' => $this->version,
            'sentry_project_id' => $this->sentryProjectId,
            'github_repository' => $this->githubRepository,
            'backup' => $this->backup->toArray(),
        ];
    }
}
