<?php

namespace Witify\Notifications\Tests;

use Illuminate\Support\Str;
use Witify\Notifications\Models\Notification;
use Witify\Notifications\Tests\Fixtures\User;

class NotificationControllerTest extends TestCase
{
    public function test_it_doesnt_list_notifications_when_guest(): void
    {
        $this->getJson('/api/notifications')->assertStatus(401);
    }

    public function test_it_lists_only_the_notifications_of_the_authenticated_user(): void
    {
        /** @var User $user */
        $user = User::factory()->create();
        /** @var User $otherUser */
        $otherUser = User::factory()->create();

        $own = $this->createNotification($user, 'Mine');
        $this->createNotification($otherUser, 'Not mine');

        $this->actingAs($user);

        $response = $this->getJson('/api/notifications')->assertOk();

        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.id', $own->id);
    }

    public function test_it_marks_a_notification_as_read(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        $notification = $this->createNotification($user, 'Unread');

        $this->actingAs($user);

        $this->patchJson('/api/notifications/' . $notification->id)
            ->assertOk()
            ->assertJsonPath('notification.id', $notification->id);

        $this->assertNotNull($notification->fresh()?->read_at);
    }

    public function test_it_doesnt_mark_the_notification_of_another_user_as_read(): void
    {
        /** @var User $user */
        $user = User::factory()->create();
        /** @var User $otherUser */
        $otherUser = User::factory()->create();

        $notification = $this->createNotification($otherUser, 'Not mine');

        $this->actingAs($user);

        $this->patchJson('/api/notifications/' . $notification->id)->assertForbidden();

        $this->assertNull($notification->fresh()?->read_at);
    }

    public function test_it_filters_unread_notifications_through_the_filter_key(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        $unread = $this->createNotification($user, 'Unread');
        $this->createNotification($user, 'Read', now());

        $this->actingAs($user);

        $response = $this->getJson('/api/notifications?filter[read]=false')->assertOk();

        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.id', $unread->id);

        $readResponse = $this->getJson('/api/notifications?filter[read]=true')->assertOk();

        $readResponse->assertJsonCount(1, 'data');
        $readResponse->assertJsonPath('data.0.text', 'Read');
    }

    public function test_a_bare_read_param_does_not_filter(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        $this->createNotification($user, 'Unread');
        $this->createNotification($user, 'Read', now());

        $this->actingAs($user);

        $this->getJson('/api/notifications?read=false')
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }

    private function createNotification(User $user, string $text, mixed $readAt = null): Notification
    {
        $notification = new Notification;

        $notification->forceFill([
            'id' => (string) Str::uuid(),
            'type' => 'Witify\\Notifications\\Tests\\FakeNotification',
            'notifiable_type' => $user->getMorphClass(),
            'notifiable_id' => $user->id,
            'text' => $text,
            'read_at' => $readAt,
        ])->save();

        return $notification;
    }
}
