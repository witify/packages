<?php

namespace Witify\Devops\ValueObjects;

final class HostingData
{
    public const LARAVEL_CLOUD_PROVIDER = 'laravel_cloud';

    public ?string $provider;

    public ?string $environmentId;

    public ?string $commitSha;

    public function __construct(?string $provider, ?string $environmentId, ?string $commitSha)
    {
        $this->provider = $provider;
        $this->environmentId = $environmentId;
        $this->commitSha = $commitSha;
    }

    public function isLaravelCloud(): bool
    {
        return $this->provider === self::LARAVEL_CLOUD_PROVIDER;
    }

    /**
     * @return array{provider: ?string, environment_id: ?string, commit_sha: ?string}
     */
    public function toArray(): array
    {
        return [
            'provider' => $this->provider,
            'environment_id' => $this->environmentId,
            'commit_sha' => $this->commitSha,
        ];
    }
}
