# witify/devops

Health endpoint that reports the state of a Laravel application to the Witify portal.

The package exposes `GET /api/devops/health`, protected by a bearer token. The response carries the latest results of the [spatie/laravel-health](https://github.com/spatie/laravel-health) checks registered by the application, plus the metadata the portal needs to link the application to its GitHub repository, Sentry project and backup bucket.

It also ships two generic checks, `CpuCheck` and `FailedJobsCheck`, and the `ClientFacingCheck` base class for checks whose label and message are shown to the client in French and English.

## Requirements

| | |
|---|---|
| PHP | 8.0 to 8.4 |
| Laravel | 8.75 to 12 |
| Database | any, only for the `failed_jobs` table read by `FailedJobsCheck` and the health results table when you keep the default result store |

`spatie/laravel-health` and `spatie/cpu-load-health-check` are installed with the package. `spatie/laravel-backup` is optional: when it is present, the backup name is reported to the portal.

## Installation

```bash
composer require witify/devops
```

The service provider is discovered automatically.

Add the two variables to `.env` (and empty placeholders to `.env.example`):

```dotenv
PORTAL_HEALTH_TOKEN=
APP_VERSION=1.0.0
```

`PORTAL_HEALTH_TOKEN` is the secret the portal sends. Generate a random one per application. The endpoint answers `404` while the token is empty, so nothing is exposed before you configure it.

`APP_VERSION` is reported to the portal. Leave it out if the application has no version.

### 1. Configure laravel-health

Skip this step if the application already uses laravel-health.

```bash
php artisan vendor:publish --tag="health-config"
php artisan vendor:publish --tag="health-migrations"
php artisan migrate
```

The default result store keeps the results in the database. Both the migration and the config are documented in the [laravel-health docs](https://spatie.be/docs/laravel-health).

### 2. Register the checks

Checks are project configuration, so the application registers them, not the package. Create `app/Providers/HealthServiceProvider.php`:

```php
<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Spatie\Health\Checks\Checks\BackupsCheck;
use Spatie\Health\Checks\Checks\DatabaseCheck;
use Spatie\Health\Checks\Checks\DebugModeCheck;
use Spatie\Health\Checks\Checks\EnvironmentCheck;
use Spatie\Health\Checks\Checks\ScheduleCheck;
use Spatie\Health\Checks\Checks\UsedDiskSpaceCheck;
use Spatie\Health\Facades\Health;
use Witify\Devops\Checks\CpuCheck;
use Witify\Devops\Checks\FailedJobsCheck;

class HealthServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Health::checks([
            CpuCheck::new(),
            FailedJobsCheck::new(),
            UsedDiskSpaceCheck::new(),
            DatabaseCheck::new(),
            DebugModeCheck::new(),
            EnvironmentCheck::new(),
            ScheduleCheck::new()->heartbeatMaxAgeInMinutes(4),
            BackupsCheck::new()
                ->name('Database Backup')
                ->onDisk('backup')
                ->locatedAt(config('backup.backup.name'))
                ->youngestBackShouldHaveBeenMadeBefore(now()->subDays(1)->subHours(2))
                ->atLeastSizeInMb(1),
        ]);
    }
}
```

Keep only the checks that apply. An application without a queue worker has no `QueueCheck`, an application without Redis has no `RedisCheck`. The portal displays whatever the application reports.

Register the provider:

- Laravel 8 to 10: add `App\Providers\HealthServiceProvider::class` to the `providers` array of `config/app.php`.
- Laravel 11 and 12: add it to `bootstrap/providers.php`.

### 3. Schedule the checks

The checks run from the scheduler. The portal reads the stored results, so results must exist before the portal calls the endpoint.

Laravel 8 to 10, in `app/Console/Kernel.php`:

```php
protected function schedule(Schedule $schedule): void
{
    $schedule->command('health:check')->everyMinute();
    $schedule->command('health:schedule-check-heartbeat')->everyMinute();
    $schedule->command('model:prune', ['--model' => \Spatie\Health\Models\HealthCheckResultHistoryItem::class])->daily();
}
```

Laravel 11 and 12, in `routes/console.php`:

```php
use Illuminate\Support\Facades\Schedule;
use Spatie\Health\Models\HealthCheckResultHistoryItem;

Schedule::command('health:check')->everyMinute();
Schedule::command('health:schedule-check-heartbeat')->everyMinute();
Schedule::command('model:prune', ['--model' => HealthCheckResultHistoryItem::class])->daily();
```

The heartbeat line is only needed when `ScheduleCheck` is registered.

### 4. Verify

```bash
php artisan health:check
curl -H "Authorization: Bearer $PORTAL_HEALTH_TOKEN" https://app.example.com/api/devops/health
```

Then add the application in the portal with the same token.

## The endpoint

`GET /api/devops/health` with the header `Authorization: Bearer <PORTAL_HEALTH_TOKEN>`.

| Response | Meaning |
|---|---|
| `404` | no token configured on the application |
| `403` | the token does not match |
| `200` | the payload below |

```json
{
    "app": "Client Example",
    "checked_at": "2026-08-06T14:00:00Z",
    "checks": [
        {
            "name": "FailedJobs",
            "label_client": { "fr": "Aucune tâche de fond en échec", "en": "No failed background jobs" },
            "status": "failed",
            "message": { "fr": "2 tâches de fond en échec", "en": "2 failed background jobs" },
            "meta": { "failed_jobs_count": 2 }
        },
        {
            "name": "Database",
            "label_client": null,
            "status": "ok",
            "message": null,
            "meta": []
        }
    ],
    "meta": {
        "environment": "production",
        "sprintify_version": "1.0.0",
        "sentry_project_id": 123456,
        "github_repository": "witify/client-example",
        "backup": { "bucket": "client-backups", "prefix": "https---client-example-com" }
    }
}
```

- `checked_at` is `null` and `checks` is empty until `health:check` has stored results.
- `label_client` and a bilingual `message` are present for `ClientFacingCheck` subclasses. Other checks report the message laravel-health stored, in both languages.
- `sprintify_version` carries `APP_VERSION`. The key name is the one the portal reads, whatever the application is.
- `sentry_project_id` comes from `SENTRY_LARAVEL_DSN` or `SENTRY_DSN`.
- `github_repository` is read from `.git/config` of the deployed checkout and cached for seven days.
- `backup` reports the `bucket` of the backup disk and the backup name of spatie/laravel-backup. Both are `null` when absent.

The response is sent with `Cache-Control: no-store, private`.

## Client-facing checks

A `ClientFacingCheck` describes a business process the client understands. Its label and message exist in French and English, resolved on the application so the portal never translates.

```php
<?php

namespace App\Health;

use App\Models\Order;
use Spatie\Health\Checks\Result;
use Spatie\Health\ResultStores\StoredCheckResults\StoredCheckResult;
use Witify\Devops\Checks\ClientFacingCheck;
use Witify\Devops\ValueObjects\LocalizedTextData;

class ShopifyImportCheck extends ClientFacingCheck
{
    public function clientLabel(): LocalizedTextData
    {
        return new LocalizedTextData(
            'Aucune commande Shopify non importée depuis plus de 15 minutes',
            'No Shopify order left unimported for more than 15 minutes',
        );
    }

    public function clientMessage(StoredCheckResult $result): LocalizedTextData
    {
        return $this->message((int) ($result->meta['pending_orders'] ?? 0));
    }

    public function run(): Result
    {
        $count = Order::query()->pendingImportFor(15)->count();

        if ($count === 0) {
            return Result::make()->ok();
        }

        return Result::make()
            ->failed($this->message($count)->fr)
            ->meta(['pending_orders' => $count]);
    }

    private function message(int $count): LocalizedTextData
    {
        return new LocalizedTextData(
            "{$count} commandes en attente d'importation",
            "{$count} orders waiting to be imported",
        );
    }
}
```

`clientMessage()` rebuilds the message from the stored `meta` because the portal reads stored results, not the `Result` returned by `run()`. Use `LocalizedTextData::fromTranslation()` and `fromTranslationChoice()` when the texts live in language files.

## Configuration

Publish the config only when a default needs to change:

```bash
php artisan vendor:publish --tag="devops-config"
```

| Key | Default | Purpose |
|---|---|---|
| `token` | `env('PORTAL_HEALTH_TOKEN')` | bearer token expected by the endpoint |
| `version` | `env('APP_VERSION')` | version reported to the portal |
| `route.prefix` | `api/devops` | URL prefix of the endpoint |
| `route.middleware` | `['api']` | middleware groups applied before the token check |
| `sentry_dsn` | `env('SENTRY_LARAVEL_DSN', env('SENTRY_DSN'))` | DSN parsed for the Sentry project id |
| `backup_disk` | `backup` | filesystem disk whose `bucket` is reported |

Translations of the bundled checks can be published with `--tag="devops-translations"`.

## Changelog and upgrades

See the [CHANGELOG](https://github.com/witify/packages/blob/main/CHANGELOG.md) of the monorepo. Every package shares the same version number.

## Contributing

This repository is a read-only split of [witify/packages](https://github.com/witify/packages). Open pull requests there, in `packages/devops`.
