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
use Witify\Devops\Actions\GetReleaseVersionAction;
use Witify\Devops\Checks\FailedJobsCheck;
use Witify\Devops\Models\FailedJob;
use Witify\Devops\Tests\Concerns\InteractsWithEnvironmentVariables;
use Witify\Devops\Tests\Fixtures\TechnicalCheck;

class PortalHealthControllerTest extends TestCase
{
    use InteractsWithEnvironmentVariables;

    private const LARAVEL_CLOUD_VARIABLES = [
        'LARAVEL_CLOUD',
        'LARAVEL_CLOUD_ENV_UUID',
        'LARAVEL_CLOUD_COMMIT_SHA',
        'LARAVEL_CLOUD_COMMIT',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        foreach (self::LARAVEL_CLOUD_VARIABLES as $variable) {
            $this->setEnvironmentVariable($variable, null);
        }

        TechnicalCheck::$runCount = 0;
        Cache::forget(GetGitHubRepositoryAction::CACHE_KEY);
        Cache::forget(GetReleaseVersionAction::CACHE_KEY);

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
            'devops.sentry_dsn' => 'https://public-key@organization.ingest.sentry.io/123456',
            'devops.sentry_additional_dsns' => [],
            'devops.github_repository' => null,
            'filesystems.disks.backup.bucket' => 'client-backups',
            'backup.backup.name' => 'https---client-example-test',
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        $this->restoreEnvironmentVariables();

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
            ->getJson('/api/devops/health', ['X-Release-Version' => 'v1.0.0'])
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
                    'sprintify_version' => 'v1.0.0',
                    'sentry_project_id' => 123456,
                    'sentry_project_ids' => [123456],
                    'github_repository' => 'witify/sprintify',
                    'hosting' => [
                        'provider' => null,
                        'environment_id' => null,
                        'commit_sha' => null,
                    ],
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
            ->assertJsonPath('meta.sentry_project_ids', [])
            ->assertJsonPath('meta.backup.bucket', null)
            ->assertJsonPath('meta.backup.prefix', null);
    }

    public function test_it_keeps_the_release_version_sent_by_the_portal(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-06 14:00:00', 'UTC'));

        $this->assertReportedVersion(null, []);
        $this->assertReportedVersion('v1.0.0', ['X-Release-Version' => 'v1.0.0']);
        $this->assertReportedVersion('v1.0.0', []);
        $this->assertReportedVersion('2.10.3', ['X-Release-Version' => '2.10.3']);

        Carbon::setTestNow(Carbon::parse('2026-08-13 13:59:00', 'UTC'));
        $this->assertReportedVersion('2.10.3', []);

        Carbon::setTestNow(Carbon::parse('2026-08-13 14:01:00', 'UTC'));
        $this->assertReportedVersion(null, []);
    }

    public function test_it_ignores_a_release_version_that_is_not_a_version(): void
    {
        $this->assertReportedVersion('v1.0.0', ['X-Release-Version' => 'v1.0.0']);

        foreach (['', 'v1', '1.0', 'latest', 'v2.0.0-beta', '<b>v2.0.0</b>', "v2.0.0\n"] as $version) {
            $this->assertReportedVersion('v1.0.0', ['X-Release-Version' => $version]);
        }
    }

    public function test_it_ignores_the_release_version_of_a_request_with_a_wrong_token(): void
    {
        $this->withToken('incorrect-health-token')
            ->getJson('/api/devops/health', ['X-Release-Version' => 'v6.6.6'])
            ->assertForbidden();

        $this->assertReportedVersion(null, []);
    }

    public function test_it_no_longer_reports_the_version_of_the_configuration(): void
    {
        config()->set('devops.version', '9.9.9');

        $this->assertReportedVersion(null, []);
    }

    /**
     * @param  array<string, string>  $headers
     */
    private function assertReportedVersion(?string $version, array $headers): void
    {
        $this->flushHeaders()
            ->withToken('expected-health-token')
            ->getJson('/api/devops/health', $headers)
            ->assertOk()
            ->assertJsonPath('meta.sprintify_version', $version);
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

    public function test_the_configured_repository_wins_over_the_git_config(): void
    {
        config()->set('devops.github_repository', 'witify/navark');

        File::partialMock();
        File::shouldReceive('isReadable')->never();

        $this->withToken('expected-health-token')
            ->getJson('/api/devops/health')
            ->assertOk()
            ->assertJsonPath('meta.github_repository', 'witify/navark');

        $this->assertNull(Cache::get(GetGitHubRepositoryAction::CACHE_KEY));
    }

    public function test_it_normalizes_a_configured_repository_url(): void
    {
        config()->set('devops.github_repository', 'https://github.com/witify/navark.git');

        $this->withToken('expected-health-token')
            ->getJson('/api/devops/health')
            ->assertOk()
            ->assertJsonPath('meta.github_repository', 'witify/navark');
    }

    public function test_it_ignores_an_invalid_configured_repository(): void
    {
        config()->set('devops.github_repository', 'not a repository');

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

        $this->withToken('expected-health-token')
            ->getJson('/api/devops/health')
            ->assertOk()
            ->assertJsonPath('meta.github_repository', 'witify/sprintify');
    }

    public function test_it_does_not_cache_a_missing_repository(): void
    {
        File::partialMock();
        File::shouldReceive('isReadable')
            ->twice()
            ->with(base_path('.git/config'))
            ->andReturnFalse();

        $this->withToken('expected-health-token')
            ->getJson('/api/devops/health')
            ->assertOk()
            ->assertJsonPath('meta.github_repository', null);

        $this->assertFalse(Cache::has(GetGitHubRepositoryAction::CACHE_KEY));

        $this->withToken('expected-health-token')
            ->getJson('/api/devops/health')
            ->assertOk()
            ->assertJsonPath('meta.github_repository', null);
    }

    public function test_it_keeps_a_repository_cached_by_version_one_point_six(): void
    {
        Cache::put('devops.github_repository', 'witify/legacy', 7 * 24 * 60 * 60);

        File::partialMock();
        File::shouldReceive('isReadable')->never();

        $this->withToken('expected-health-token')
            ->getJson('/api/devops/health')
            ->assertOk()
            ->assertJsonPath('meta.github_repository', 'witify/legacy');
    }

    public function test_it_reads_the_frontend_sentry_dsn_from_vite_when_the_frontend_one_is_empty(): void
    {
        $this->setEnvironmentVariable('SENTRY_FRONTEND_DSN', '');
        $this->setEnvironmentVariable('VITE_SENTRY_DSN', 'https://frontend-key@organization.ingest.sentry.io/654321');

        $config = require __DIR__ . '/../config/devops.php';

        $this->assertSame(
            ['https://frontend-key@organization.ingest.sentry.io/654321'],
            $config['sentry_additional_dsns'],
        );
    }

    public function test_it_prefers_the_frontend_sentry_dsn_over_the_vite_one(): void
    {
        $this->setEnvironmentVariable('SENTRY_FRONTEND_DSN', 'https://frontend-key@organization.ingest.sentry.io/111111');
        $this->setEnvironmentVariable('VITE_SENTRY_DSN', 'https://vite-key@organization.ingest.sentry.io/222222');

        $config = require __DIR__ . '/../config/devops.php';

        $this->assertSame(
            ['https://frontend-key@organization.ingest.sentry.io/111111'],
            $config['sentry_additional_dsns'],
        );
    }

    public function test_it_reports_every_sentry_project_once_in_order(): void
    {
        config()->set('devops.sentry_additional_dsns', [
            'https://frontend-key@organization.ingest.sentry.io/654321',
            'https://other-key@organization.ingest.sentry.io/123456',
            'not a DSN',
            'https://frontend-key@organization.ingest.sentry.io/654321',
        ]);

        $this->withToken('expected-health-token')
            ->getJson('/api/devops/health')
            ->assertOk()
            ->assertJsonPath('meta.sentry_project_id', 123456)
            ->assertJsonPath('meta.sentry_project_ids', [123456, 654321]);
    }

    public function test_it_reports_the_additional_sentry_projects_without_a_main_dsn(): void
    {
        config()->set([
            'devops.sentry_dsn' => null,
            'devops.sentry_additional_dsns' => ['https://frontend-key@organization.ingest.sentry.io/654321'],
        ]);

        $this->withToken('expected-health-token')
            ->getJson('/api/devops/health')
            ->assertOk()
            ->assertJsonPath('meta.sentry_project_id', null)
            ->assertJsonPath('meta.sentry_project_ids', [654321]);
    }

    public function test_it_reports_the_laravel_cloud_hosting(): void
    {
        $this->setEnvironmentVariable('LARAVEL_CLOUD', '1');
        $this->setEnvironmentVariable('LARAVEL_CLOUD_ENV_UUID', 'env-9f3c2b1a');
        $this->setEnvironmentVariable('LARAVEL_CLOUD_COMMIT_SHA', 'a1b2c3d4e5f6');

        $this->withToken('expected-health-token')
            ->getJson('/api/devops/health')
            ->assertOk()
            ->assertJsonPath('meta.hosting', [
                'provider' => 'laravel_cloud',
                'environment_id' => 'env-9f3c2b1a',
                'commit_sha' => 'a1b2c3d4e5f6',
            ]);
    }

    public function test_it_detects_laravel_cloud_from_its_environment_id_alone(): void
    {
        $this->setEnvironmentVariable('LARAVEL_CLOUD_ENV_UUID', 'env-9f3c2b1a');
        $this->setEnvironmentVariable('LARAVEL_CLOUD_COMMIT', 'f6e5d4c3b2a1');

        $this->withToken('expected-health-token')
            ->getJson('/api/devops/health')
            ->assertOk()
            ->assertJsonPath('meta.hosting', [
                'provider' => 'laravel_cloud',
                'environment_id' => 'env-9f3c2b1a',
                'commit_sha' => 'f6e5d4c3b2a1',
            ]);
    }

    public function test_it_prefers_the_commit_sha_variable_over_the_commit_one(): void
    {
        $this->setEnvironmentVariable('LARAVEL_CLOUD', '1');
        $this->setEnvironmentVariable('LARAVEL_CLOUD_COMMIT_SHA', 'a1b2c3d4e5f6');
        $this->setEnvironmentVariable('LARAVEL_CLOUD_COMMIT', 'f6e5d4c3b2a1');

        $this->withToken('expected-health-token')
            ->getJson('/api/devops/health')
            ->assertOk()
            ->assertJsonPath('meta.hosting.commit_sha', 'a1b2c3d4e5f6');
    }

    public function test_it_reads_laravel_cloud_from_the_process_environment(): void
    {
        $this->setEnvironmentVariable('LARAVEL_CLOUD', null);
        putenv('LARAVEL_CLOUD=1');

        $this->withToken('expected-health-token')
            ->getJson('/api/devops/health')
            ->assertOk()
            ->assertJsonPath('meta.hosting.provider', 'laravel_cloud')
            ->assertJsonPath('meta.hosting.environment_id', null);
    }

    public function test_it_reports_no_hosting_outside_laravel_cloud(): void
    {
        $this->setEnvironmentVariable('LARAVEL_CLOUD', '0');

        $this->withToken('expected-health-token')
            ->getJson('/api/devops/health')
            ->assertOk()
            ->assertJsonPath('meta.hosting', [
                'provider' => null,
                'environment_id' => null,
                'commit_sha' => null,
            ]);
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
