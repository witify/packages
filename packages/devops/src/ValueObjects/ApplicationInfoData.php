<?php

namespace Witify\Devops\ValueObjects;

final class ApplicationInfoData
{
    public string $name;

    public string $environment;

    public ?string $version;

    public string $phpVersion;

    public string $laravelVersion;

    public bool $debug;

    public bool $maintenance;

    public bool $configurationCached;

    public bool $routesCached;

    public ?string $githubRepository;

    public ?int $sentryProjectId;

    public function __construct(
        string $name,
        string $environment,
        ?string $version,
        string $phpVersion,
        string $laravelVersion,
        bool $debug,
        bool $maintenance,
        bool $configurationCached,
        bool $routesCached,
        ?string $githubRepository,
        ?int $sentryProjectId
    ) {
        $this->name = $name;
        $this->environment = $environment;
        $this->version = $version;
        $this->phpVersion = $phpVersion;
        $this->laravelVersion = $laravelVersion;
        $this->debug = $debug;
        $this->maintenance = $maintenance;
        $this->configurationCached = $configurationCached;
        $this->routesCached = $routesCached;
        $this->githubRepository = $githubRepository;
        $this->sentryProjectId = $sentryProjectId;
    }

    public function githubUrl(): ?string
    {
        return $this->githubRepository ? 'https://github.com/' . $this->githubRepository : null;
    }
}
