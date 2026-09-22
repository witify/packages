<?php

namespace Witify\Devops\Tests;

use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Witify\Devops\Actions\ResolveDeveloperToolsAction;

class DisabledConsoleTest extends TestCase
{
    /**
     * @param  Application  $app
     */
    protected function getEnvironmentSetUp($app): void
    {
        parent::getEnvironmentSetUp($app);

        $app['config']->set('devops.console.path', null);
        $app['config']->set('devops.console.health_path', null);
    }

    public function test_it_registers_no_page_when_both_paths_are_null(): void
    {
        $this->assertFalse(Route::has('devops.console'));
        $this->assertFalse(Route::has('devops.console.sentry_test'));
        $this->assertFalse(Route::has('devops.health'));
        $this->assertTrue(Route::has('api.devops.health.show'));
    }

    public function test_it_offers_no_health_tool_without_the_health_page(): void
    {
        $tools = app(ResolveDeveloperToolsAction::class)->handle();

        $this->assertSame([], array_map(fn ($tool) => $tool->key, $tools));
    }
}
