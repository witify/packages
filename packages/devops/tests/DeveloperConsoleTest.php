<?php

namespace Witify\Devops\Tests;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Mockery;
use RuntimeException;
use Spatie\Health\Commands\RunHealthChecksCommand;
use Spatie\Health\Facades\Health;
use Witify\Devops\Actions\GetGitHubRepositoryAction;
use Witify\Devops\Actions\GetReleaseVersionAction;
use Witify\Devops\Tests\Concerns\InteractsWithEnvironmentVariables;
use Witify\Devops\Tests\Fixtures\TechnicalCheck;

class DeveloperConsoleTest extends TestCase
{
    use InteractsWithEnvironmentVariables;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setEnvironmentVariable('LARAVEL_CLOUD', null);
        $this->setEnvironmentVariable('LARAVEL_CLOUD_ENV_UUID', null);

        Cache::forever(GetGitHubRepositoryAction::CACHE_KEY, 'witify/sprintify');

        config()->set([
            'app.name' => 'Client Example',
            'app.debug' => false,
            'devops.sentry_dsn' => 'https://public-key@organization.ingest.sentry.io/123456',
            'devops.sentry_additional_dsns' => [],
            'devops.github_repository' => null,
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        $this->restoreEnvironmentVariables();

        parent::tearDown();
    }

    public function test_it_lists_the_detected_tools_and_the_configured_links(): void
    {
        config()->set('devops.console.links', [
            'logs' => '/my-logs',
            'forge' => ['label' => 'Forge', 'description' => 'Server panel', 'url' => 'https://forge.laravel.com'],
        ]);

        $response = $this->get('/devops')->assertOk();

        $response->assertSee('data-tool="health"', false);
        $response->assertSee('href="' . url('/status') . '"', false);
        $response->assertSee('Laravel Health');
        $response->assertSee('data-tool="logs"', false);
        $response->assertSee('href="/my-logs"', false);
        $response->assertSee('Forge');
        $response->assertSee('Server panel');
        $response->assertDontSee('Horizon');
        $response->assertDontSee('Pulse');
        $response->assertDontSee('Telescope');
    }

    public function test_it_shows_the_application_information(): void
    {
        Cache::forever(GetReleaseVersionAction::CACHE_KEY, 'v2.4.0');

        $this->get('/devops')
            ->assertOk()
            ->assertSee('Client Example')
            ->assertSee('<th>Version</th><td>v2.4.0</td>', false)
            ->assertSee(PHP_VERSION)
            ->assertSee(app()->version())
            ->assertSee('href="https://github.com/witify/sprintify"', false)
            ->assertSee('<th>Sentry project</th>', false)
            ->assertSee('123456')
            ->assertDontSee('<th>Hosting</th>', false);
    }

    public function test_it_shows_every_sentry_project_and_the_laravel_cloud_hosting(): void
    {
        config()->set([
            'devops.sentry_additional_dsns' => ['https://frontend-key@organization.ingest.sentry.io/654321'],
            'devops.github_repository' => 'witify/navark',
        ]);
        $this->setEnvironmentVariable('LARAVEL_CLOUD', '1');
        $this->setEnvironmentVariable('LARAVEL_CLOUD_ENV_UUID', 'env-9f3c2b1a');

        $this->get('/devops')
            ->assertOk()
            ->assertSee('href="https://github.com/witify/navark"', false)
            ->assertSee('<th>Sentry projects</th>', false)
            ->assertSee('123456, 654321')
            ->assertSee('<th>Hosting</th>', false)
            ->assertSee('Laravel Cloud')
            ->assertSee('env-9f3c2b1a');
    }

    public function test_it_shows_an_unknown_sentry_project_without_a_dsn(): void
    {
        config()->set('devops.sentry_dsn', null);

        $this->get('/devops')
            ->assertOk()
            ->assertSee('<th>Sentry project</th>', false)
            ->assertSee('unknown');
    }

    public function test_it_hides_the_version_until_the_portal_sends_one(): void
    {
        config()->set('devops.version', '9.9.9');

        $this->get('/devops')
            ->assertOk()
            ->assertDontSee('<th>Version</th>', false)
            ->assertDontSee('9.9.9');
    }

    public function test_it_tells_when_no_health_results_exist_yet(): void
    {
        $this->get('/devops')
            ->assertOk()
            ->assertSee('No results yet');
    }

    public function test_it_summarizes_the_latest_health_results(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-06 14:00:00', 'UTC'));

        Health::clearChecks();
        Health::checks([TechnicalCheck::new()]);

        Artisan::call(RunHealthChecksCommand::class, ['--no-notification' => true]);

        $this->get('/devops')
            ->assertOk()
            ->assertSee('Last run 2026-08-06 14:00:00')
            ->assertSee('1 warning')
            ->assertSee(url('/status'));
    }

    public function test_it_serves_the_health_results_page(): void
    {
        $this->get('/status')->assertOk();
    }

    public function test_it_hides_the_sentry_test_when_sentry_is_not_installed(): void
    {
        $this->get('/devops')
            ->assertOk()
            ->assertDontSee('Sentry Test');

        $this->post('/devops/sentry-test')->assertNotFound();
    }

    public function test_it_sends_a_test_exception_to_sentry(): void
    {
        $sentry = Mockery::mock();
        $sentry->shouldReceive('captureException')
            ->once()
            ->with(Mockery::type(RuntimeException::class))
            ->andReturn('0a3e56e2be084ab5889c445f62b05172');

        $this->app->instance('sentry', $sentry);

        $this->get('/devops')
            ->assertOk()
            ->assertSee('Sentry Test');

        $this->post('/devops/sentry-test')
            ->assertRedirect(url('/devops'));

        $this->get('/devops')
            ->assertOk()
            ->assertSee('Backend sent')
            ->assertSee('Event ID: 0a3e56e2be084ab5889c445f62b05172');
    }
}
