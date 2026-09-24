<?php

namespace Witify\Notifications\Tests;

use Witify\Notifications\Actions\GetUserNotificationsAction;
use Witify\Notifications\Models\NotificationMessage;

final class NotificationMessageSanitizationTest extends TestCase
{
    public function test_message_html_is_sanitized_on_save(): void
    {
        $notificationMessage = NotificationMessage::query()->create([
            'notification_class' => 'App\Notifications\FakeNotification',
            'locale' => 'en',
            'channel' => GetUserNotificationsAction::CHANNEL_MAIL,
            'subject' => 'Subject',
            'message' => '<p><strong>Hello</strong> :first_name</p><script>alert("xss")</script>',
        ]);

        $this->assertStringNotContainsString('<script>', $notificationMessage->message);
        $this->assertStringNotContainsString('alert("xss")', $notificationMessage->message);
        $this->assertStringContainsString('<p><strong>Hello</strong> :first_name</p>', $notificationMessage->message);

        $this->assertSame(
            $notificationMessage->message,
            NotificationMessage::query()->findOrFail($notificationMessage->id)->message,
        );
    }

    public function test_rich_text_markup_used_by_default_messages_survives_sanitization(): void
    {
        $message = '<p><strong>Platform:</strong> Chrome<br><a href="https://example.test/admin">Review</a></p>';

        $notificationMessage = NotificationMessage::query()->create([
            'notification_class' => 'App\Notifications\FakeNotification',
            'locale' => 'en',
            'channel' => GetUserNotificationsAction::CHANNEL_DATABASE,
            'subject' => '',
            'message' => $message,
        ]);

        $this->assertStringContainsString('<strong>Platform:</strong>', $notificationMessage->message);
        $this->assertStringContainsString('<br />', $notificationMessage->message);
        $this->assertStringContainsString('href="https://example.test/admin"', $notificationMessage->message);
    }
}
