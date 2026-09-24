<?php

namespace Witify\Notifications\Tests;

use Witify\Notifications\Actions\GetUserNotificationsAction;
use Witify\Notifications\Tests\Fixtures\BuilderHeraldNotification;
use Witify\Notifications\Tests\Fixtures\ResetPasswordNotification;
use Witify\Notifications\Tests\Fixtures\User;

final class HeraldNotificationControllerTest extends TestCase
{
    public function test_index_returns_shared_herald_notification_summary(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->getJson(route('api.herald_notifications.index'));

        $response->assertOk();

        $notification = collect((array) $response->json('data'))
            ->firstWhere('key', 'reset_password');

        $this->assertSame(ResetPasswordNotification::class, $notification['class']);
        $this->assertNotEmpty($notification['title']);
        $this->assertSame('System', $notification['group']);
        $this->assertFalse($notification['toggleable']);
        $this->assertFalse($notification['customizable']);
        $this->assertSame([GetUserNotificationsAction::CHANNEL_MAIL], $notification['supported_channels']);
    }

    public function test_show_extends_the_shared_herald_notification_summary(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postJson(route('api.herald_notifications.show', [
                'notification_class' => ResetPasswordNotification::class,
            ]));

        $response->assertOk();
        $response->assertJsonPath('data.key', 'reset_password');
        $response->assertJsonPath('data.class', ResetPasswordNotification::class);
        $response->assertJsonPath('data.toggleable', false);
        $response->assertJsonPath('data.customizable', false);
        $response->assertJsonStructure([
            'data' => [
                'notification_messages',
                'previews',
                'variables',
            ],
        ]);
    }

    public function test_show_rejects_unregistered_notification_classes(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson(route('api.herald_notifications.show', [
                'notification_class' => BuilderHeraldNotification::class,
            ]))
            ->assertNotFound();
    }

    public function test_notification_messages_cannot_customize_required_notifications(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->patchJson(route('api.notification_messages.update.batch'), [
                'notification_messages' => [
                    [
                        'notification_class' => ResetPasswordNotification::class,
                        'locale' => 'en',
                        'channel' => GetUserNotificationsAction::CHANNEL_MAIL,
                        'subject' => 'Custom subject',
                        'message' => 'Custom message',
                        'customized' => true,
                    ],
                ],
            ]);

        $response->assertUnprocessable();
        $response->assertInvalid([
            'notification_messages.0.notification_class',
        ]);
    }
}
