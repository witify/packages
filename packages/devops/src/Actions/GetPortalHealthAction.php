<?php

namespace Witify\Devops\Actions;

use Carbon\CarbonImmutable;
use Spatie\Health\Health;
use Spatie\Health\ResultStores\ResultStore;
use Spatie\Health\ResultStores\StoredCheckResults\StoredCheckResult;
use Witify\Devops\Checks\ClientFacingCheck;
use Witify\Devops\ValueObjects\LocalizedTextData;
use Witify\Devops\ValueObjects\PortalHealthBackupData;
use Witify\Devops\ValueObjects\PortalHealthCheckData;
use Witify\Devops\ValueObjects\PortalHealthData;
use Witify\Devops\ValueObjects\PortalHealthMetadataData;

class GetPortalHealthAction
{
    private ResultStore $resultStore;

    private Health $health;

    private GetGitHubRepositoryAction $getGitHubRepositoryAction;

    public function __construct(
        ResultStore $resultStore,
        Health $health,
        GetGitHubRepositoryAction $getGitHubRepositoryAction
    ) {
        $this->resultStore = $resultStore;
        $this->health = $health;
        $this->getGitHubRepositoryAction = $getGitHubRepositoryAction;
    }

    public function handle(): PortalHealthData
    {
        $latestResults = $this->resultStore->latestResults();
        $clientFacingChecks = $this->health->registeredChecks()
            ->filter(fn ($check): bool => $check instanceof ClientFacingCheck)
            ->keyBy(fn (ClientFacingCheck $check): string => $check->getName());

        $checks = $latestResults
            ? $latestResults->storedCheckResults
                ->map(function (StoredCheckResult $result) use ($clientFacingChecks): PortalHealthCheckData {
                    /** @var ClientFacingCheck|null $clientFacingCheck */
                    $clientFacingCheck = $clientFacingChecks->get($result->name);

                    return new PortalHealthCheckData(
                        $result->name,
                        $clientFacingCheck ? $clientFacingCheck->clientLabel() : null,
                        $result->status,
                        $clientFacingCheck
                            ? $clientFacingCheck->clientMessage($result)
                            : LocalizedTextData::fromString($result->notificationMessage),
                        $result->meta,
                    );
                })
                ->values()
                ->all()
            : [];

        $checkedAt = $latestResults
            ? CarbonImmutable::instance($latestResults->finishedAt)
                ->utc()
                ->format('Y-m-d\TH:i:s\Z')
            : null;

        $backupDisk = (string) config('devops.backup_disk');

        return new PortalHealthData(
            (string) config('app.name'),
            $checkedAt,
            $checks,
            new PortalHealthMetadataData(
                (string) config('app.env'),
                $this->nullableString(config('devops.version')),
                (new ParseSentryProjectIdAction($this->nullableString(config('devops.sentry_dsn'))))->handle(),
                $this->getGitHubRepositoryAction->handle(),
                new PortalHealthBackupData(
                    $this->nullableString(config("filesystems.disks.{$backupDisk}.bucket")),
                    $this->nullableString(config('backup.backup.name')),
                ),
            ),
        );
    }

    /**
     * @param  mixed  $value
     */
    private function nullableString($value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (string) $value;
    }
}
