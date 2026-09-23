<?php

namespace Witify\Devops\Tests;

use Illuminate\Auth\GenericUser;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Event;
use Witify\Devops\Events\EchoTestEvent;

class EchoTestTest extends TestCase
{
    /**
     * @param  Application  $app
     */
    protected function getEnvironmentSetUp($app): void
    {
        parent::getEnvironmentSetUp($app);

        $app['config']->set('devops.console.echo_channel', 'user.{id}');
        $app['config']->set('broadcasting.default', 'reverb');
        $app['config']->set('broadcasting.connections.reverb', [
            'driver' => 'reverb',
            'key' => 'reverb-app-key',
            'secret' => 'reverb-app-secret',
            'app_id' => 'reverb-app-id',
            'options' => [
                'host' => 'ws.example.com',
                'port' => 443,
                'scheme' => 'https',
            ],
        ]);
        $app['config']->set('broadcasting.connections.null', ['driver' => 'null']);
    }

    public function test_it_shows_the_echo_test_with_the_broadcaster_settings_of_the_application(): void
    {
        $response = $this->actingAs(new GenericUser(['id' => 7]))->get('/devops')->assertOk();

        $response->assertSee('id="echo-test"', false);
        $response->assertSee('private-user.7');
        $response->assertSee('echo.iife.js');

        $echo = $this->echoConfigOf($response->getContent());

        $this->assertSame('reverb', $echo['broadcaster']);
        $this->assertSame('reverb-app-key', $echo['key']);
        $this->assertSame('ws.example.com', $echo['host']);
        $this->assertSame(443, $echo['port']);
        $this->assertTrue($echo['force_tls']);
        $this->assertNull($echo['cluster']);
        $this->assertSame('user.7', $echo['channel']);
        $this->assertSame('devops.echo_test', $echo['event']);
        $this->assertSame(url('/broadcasting/auth'), $echo['auth_endpoint']);
        $this->assertSame(url('/devops/echo-test'), $echo['send_url']);
    }

    public function test_it_reads_the_cluster_and_tls_flag_of_a_pusher_connection(): void
    {
        config()->set('broadcasting.default', 'pusher');
        config()->set('broadcasting.connections.pusher', [
            'driver' => 'pusher',
            'key' => 'pusher-key',
            'options' => ['cluster' => 'mt1', 'useTLS' => false],
        ]);

        $echo = $this->echoConfigOf($this->actingAs(new GenericUser(['id' => 7]))->get('/devops')->getContent());

        $this->assertSame('pusher', $echo['broadcaster']);
        $this->assertSame('mt1', $echo['cluster']);
        $this->assertNull($echo['host']);
        $this->assertFalse($echo['force_tls']);
    }

    public function test_it_hides_the_echo_test_without_a_channel(): void
    {
        config()->set('devops.console.echo_channel', null);

        $this->actingAs(new GenericUser(['id' => 7]))->get('/devops')->assertOk()->assertDontSee('id="echo-test"', false);
        $this->actingAs(new GenericUser(['id' => 7]))->postJson('/devops/echo-test')->assertNotFound();
    }

    public function test_it_hides_the_echo_test_when_the_broadcaster_is_not_pusher_compatible(): void
    {
        config()->set('broadcasting.default', 'null');

        $this->actingAs(new GenericUser(['id' => 7]))->get('/devops')->assertOk()->assertDontSee('id="echo-test"', false);
        $this->actingAs(new GenericUser(['id' => 7]))->postJson('/devops/echo-test')->assertNotFound();
    }

    public function test_it_hides_the_echo_test_for_a_guest(): void
    {
        $this->get('/devops')->assertOk()->assertDontSee('id="echo-test"', false);
        $this->postJson('/devops/echo-test')->assertForbidden();
    }

    public function test_it_broadcasts_the_message_on_the_private_channel_of_the_user(): void
    {
        Event::fake([EchoTestEvent::class]);

        $this->actingAs(new GenericUser(['id' => 7]))
            ->postJson('/devops/echo-test', ['message' => 'Ping'])
            ->assertOk()
            ->assertJsonPath('channel', 'private-user.7')
            ->assertJsonPath('message', 'Ping')
            ->assertJsonStructure(['channel', 'message', 'sent_at']);

        Event::assertDispatched(EchoTestEvent::class, function (EchoTestEvent $event): bool {
            return $event->broadcastOn()->name === 'private-user.7'
                && $event->broadcastAs() === 'devops.echo_test'
                && $event->broadcastWith()['message'] === 'Ping';
        });
    }

    public function test_it_falls_back_to_a_default_message(): void
    {
        Event::fake([EchoTestEvent::class]);

        $this->actingAs(new GenericUser(['id' => 7]))
            ->postJson('/devops/echo-test')
            ->assertOk()
            ->assertJsonPath('message', 'Hello from Echo!');

        Event::assertDispatched(EchoTestEvent::class, fn (EchoTestEvent $event): bool => $event->message === 'Hello from Echo!');
    }

    public function test_it_rejects_a_message_that_is_too_long(): void
    {
        Event::fake([EchoTestEvent::class]);

        $this->actingAs(new GenericUser(['id' => 7]))
            ->postJson('/devops/echo-test', ['message' => str_repeat('a', 256)])
            ->assertUnprocessable();

        Event::assertNotDispatched(EchoTestEvent::class);
    }

    /**
     * @return array<string, mixed>
     */
    private function echoConfigOf(string $html): array
    {
        $this->assertSame(1, preg_match('/data-echo="([^"]+)"/', $html, $matches));

        /** @var array<string, mixed> $decoded */
        $decoded = json_decode(html_entity_decode($matches[1], ENT_QUOTES), true);

        return $decoded;
    }
}
