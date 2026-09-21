<?php

namespace Witify\Devops\ValueObjects;

final class PortalHealthData
{
    public string $app;

    public ?string $checkedAt;

    /** @var list<PortalHealthCheckData> */
    public array $checks;

    public PortalHealthMetadataData $metadata;

    /**
     * @param  list<PortalHealthCheckData>  $checks
     */
    public function __construct(string $app, ?string $checkedAt, array $checks, PortalHealthMetadataData $metadata)
    {
        $this->app = $app;
        $this->checkedAt = $checkedAt;
        $this->checks = $checks;
        $this->metadata = $metadata;
    }

    /**
     * @return array{app: string, checked_at: ?string, checks: list<array{name: string, label_client: ?array{fr: string, en: string}, status: string, message: ?array{fr: string, en: string}, meta: array<string, mixed>}>, meta: array{environment: string, sprintify_version: ?string, sentry_project_id: ?int, github_repository: ?string, backup: array{bucket: ?string, prefix: ?string}}}
     */
    public function toArray(): array
    {
        return [
            'app' => $this->app,
            'checked_at' => $this->checkedAt,
            'checks' => array_map(
                fn (PortalHealthCheckData $check): array => $check->toArray(),
                $this->checks,
            ),
            'meta' => $this->metadata->toArray(),
        ];
    }
}
