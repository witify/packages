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
use Witify\Devops\Tests\Fixtures\TechnicalCheck;

class DeveloperConsoleTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Cache::forever(GetGitHubRepositoryAction::CACHE_KEY, 'witify/sprintify');

        config()->set([
            'app.name' => 'Client Example',
            'app.debug' => false,
            'devops.version' => '2.4.0',
            'devops.sentry_dsn' => 'https://public-key@organization.ingest.sentry.io/123456',
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

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
        $this->get('/devops')
            ->assertOk()
            ->assertSee('Client Example')
            ->assertSee('2.4.0')
            ->assertSee(PHP_VERSION)
            ->assertSee(app()->version())
            ->assertSee('href="https://github.com/witify/sprintify"', false)
            ->assertSee('123456');
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
            ->assertDontSee('Sentry test');

        $this->post('/devops/sentry-test')->assertNotFound();
    }

    public function test_it_sends_a_test_exception_to_sentry(): void
    {
        $sentry = Mockery::mock();
        $sentry->shouldReceive('captureException')
            ->once()
            ->with(Mockery::type(RuntimeException::class));

        $this->app->instance('sentry', $sentry);

        $this->get('/devops')
            ->assertOk()
            ->assertSee('Sentry test');

        $this->post('/devops/sentry-test')
            ->assertRedirect(url('/devops'));

        $this->get('/devops')
            ->assertOk()
            ->assertSee('Test exception sent');
    }
}
