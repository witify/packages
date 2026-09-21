<?php

namespace Witify\Devops\Tests;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Spatie\Health\Commands\RunHealthChecksCommand;
use Spatie\Health\Facades\Health;
use Witify\Devops\Actions\GetGitHubRepositoryAction;
use Witify\Devops\Checks\FailedJobsCheck;
use Witify\Devops\Models\FailedJob;
use Witify\Devops\Tests\Fixtures\TechnicalCheck;

class PortalHealthControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        TechnicalCheck::$runCount = 0;
        Cache::forget(GetGitHubRepositoryAction::CACHE_KEY);

        Schema::create('failed_jobs', function (Blueprint $table): void {
            $table->id();
            $table->string('uuid')->unique();
            $table->text('connection');
            $table->text('queue');
            $table->longText('payload');
            $table->longText('exception');
            $table->timestamp('failed_at')->useCurrent();
        });

        config()->set([
            'app.name' => 'Client Example',
            'app.env' => 'production',
            'devops.token' => 'expected-health-token',
            'devops.version' => '1.0.0',
            'devops.sentry_dsn' => 'https://public-key@organization.ingest.sentry.io/123456',
            'filesystems.disks.backup.bucket' => 'client-backups',
            'backup.backup.name' => 'https---client-example-test',
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_it_is_not_exposed_without_a_configured_token(): void
    {
        config()->set('devops.token');

        $this->getJson('/api/devops/health')->assertNotFound();
    }

    public function test_it_rejects_an_invalid_token(): void
    {
        $this->withToken('incorrect-health-token')
            ->getJson('/api/devops/health')
            ->assertForbidden();
    }

    public function test_it_returns_the_latest_stored_health_results_and_project_metadata(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-06 14:00:00', 'UTC'));

        $gitConfig = file_get_contents(__DIR__ . '/Fixtures/git-config');

        $this->assertIsString($gitConfig);

        File::partialMock();
        File::shouldReceive('isReadable')
            ->once()
            ->with(base_path('.git/config'))
            ->andReturnTrue();
        File::shouldReceive('sharedGet')
            ->once()
            ->with(base_path('.git/config'))
            ->andReturn($gitConfig);

        Health::clearChecks();
        Health::checks([
            FailedJobsCheck::new(),
            TechnicalCheck::new(),
        ]);

        Artisan::call(RunHealthChecksCommand::class, ['--no-notification' => true]);

        $this->assertSame(1, TechnicalCheck::$runCount);

        $response = $this->withToken('expected-health-token')
            ->getJson('/api/devops/health')
            ->assertOk()
            ->assertExactJson([
                'app' => 'Client Example',
                'checked_at' => '2026-08-06T14:00:00Z',
                'checks' => [
                    [
                        'name' => 'FailedJobs',
                        'label_client' => [
                            'fr' => 'Aucune tâche de fond en échec',
                            'en' => 'No failed background jobs',
                        ],
                        'status' => 'ok',
                        'message' => [
                            'fr' => 'Aucune tâche de fond en échec',
                            'en' => 'No failed background jobs',
                        ],
                        'meta' => [],
                    ],
                    [
                        'name' => 'Technical',
                        'label_client' => null,
                        'status' => 'warning',
                        'message' => [
                            'fr' => 'Technical warning',
                            'en' => 'Technical warning',
                        ],
                        'meta' => ['threshold' => 10],
                    ],
                ],
                'meta' => [
                    'environment' => 'production',
                    'sprintify_version' => '1.0.0',
                    'sentry_project_id' => 123456,
                    'github_repository' => 'witify/sprintify',
                    'backup' => [
                        'bucket' => 'client-backups',
                        'prefix' => 'https---client-example-test',
                    ],
                ],
            ]);

        $this->assertSame(1, TechnicalCheck::$runCount);
        $response->assertHeader('Cache-Control', 'no-store, private');

        $this->withToken('expected-health-token')
            ->getJson('/api/devops/health')
            ->assertOk()
            ->assertJsonPath('meta.github_repository', 'witify/sprintify');

        $this->assertSame(1, TechnicalCheck::$runCount);
    }

    public function test_it_returns_an_empty_snapshot_when_no_results_have_been_stored_and_git_is_unavailable(): void
    {
        File::partialMock();
        File::shouldReceive('isReadable')
            ->once()
            ->with(base_path('.git/config'))
            ->andReturnFalse();

        $this->withToken('expected-health-token')
            ->getJson('/api/devops/health')
            ->assertOk()
            ->assertJsonPath('checked_at', null)
            ->assertJsonPath('checks', [])
            ->assertJsonPath('meta.github_repository', null);
    }

    public function test_it_reports_null_metadata_when_the_host_has_no_version_sentry_or_backup(): void
    {
        config()->set([
            'devops.version' => null,
            'devops.sentry_dsn' => null,
            'filesystems.disks.backup.bucket' => null,
            'backup.backup.name' => null,
        ]);

        Cache::forever(GetGitHubRepositoryAction::CACHE_KEY, 'witify/sprintify');

        $this->withToken('expected-health-token')
            ->getJson('/api/devops/health')
            ->assertOk()
            ->assertJsonPath('meta.sprintify_version', null)
            ->assertJsonPath('meta.sentry_project_id', null)
            ->assertJsonPath('meta.backup.bucket', null)
            ->assertJsonPath('meta.backup.prefix', null);
    }

    public function test_it_returns_a_null_repository_when_the_git_config_disappears_before_reading(): void
    {
        File::partialMock();
        File::shouldReceive('isReadable')
            ->once()
            ->with(base_path('.git/config'))
            ->andReturnTrue();
        File::shouldReceive('sharedGet')
            ->once()
            ->with(base_path('.git/config'))
            ->andReturn('');

        $this->withToken('expected-health-token')
            ->getJson('/api/devops/health')
            ->assertOk()
            ->assertJsonPath('meta.github_repository', null);
    }

    public function test_it_reports_failed_background_jobs_through_the_endpoint(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-06 14:00:00', 'UTC'));

        Cache::forever(GetGitHubRepositoryAction::CACHE_KEY, 'witify/sprintify');

        FailedJob::query()->create([
            'uuid' => '3fe78f94-f46c-4a7e-a0ec-a1e1178fd75c',
            'connection' => 'redis',
            'queue' => 'default',
            'payload' => '{}',
            'exception' => 'Example failure',
            'failed_at' => Carbon::parse('2026-08-06 13:55:00', 'UTC'),
        ]);

        Health::clearChecks();
        Health::checks([FailedJobsCheck::new()]);

        Artisan::call(RunHealthChecksCommand::class, ['--no-notification' => true]);

        $this->withToken('expected-health-token')
            ->getJson('/api/devops/health')
            ->assertOk()
            ->assertJsonPath('checks.0.name', 'FailedJobs')
            ->assertJsonPath('checks.0.status', 'failed')
            ->assertJsonPath('checks.0.label_client.en', 'No failed background jobs')
            ->assertJsonPath('checks.0.message.fr', '1 tâche de fond en échec')
            ->assertJsonPath('checks.0.message.en', '1 failed background job')
            ->assertJsonPath('checks.0.meta.failed_jobs_count', 1);

        FailedJob::query()->create([
            'uuid' => '639e9e23-3b8c-44ee-ac95-9af50bc93814',
            'connection' => 'redis',
            'queue' => 'default',
            'payload' => '{}',
            'exception' => 'Second example failure',
            'failed_at' => Carbon::parse('2026-08-06 13:56:00', 'UTC'),
        ]);

        Carbon::setTestNow(Carbon::parse('2026-08-06 14:01:00', 'UTC'));

        Artisan::call(RunHealthChecksCommand::class, ['--no-notification' => true]);

        $this->withToken('expected-health-token')
            ->getJson('/api/devops/health')
            ->assertOk()
            ->assertJsonPath('checks.0.message.fr', '2 tâches de fond en échec')
            ->assertJsonPath('checks.0.message.en', '2 failed background jobs')
            ->assertJsonPath('checks.0.meta.failed_jobs_count', 2);
    }
}
