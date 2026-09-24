<?php

namespace Witify\Notifications\Tests;

use Illuminate\Notifications\SendQueuedNotifications;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Notification as NotificationFacade;
use Witify\Notifications\Actions\GetUserNotificationsAction;
use Witify\Notifications\Actions\UpdateNotificationMessagesAction;
use Witify\Notifications\Herald\Herald;
use Witify\Notifications\Herald\HeraldNotifiable;
use Witify\Notifications\Models\NotificationMessage;
use Witify\Notifications\Tests\Fixtures\BuilderHeraldNotification;
use Witify\Notifications\Tests\Fixtures\User;
use Witify\Notifications\ValueObjects\HeraldNotificationDetailsData;
use Witify\Notifications\ValueObjects\NotificationMessageBatchData;
use Witify\Notifications\ValueObjects\NotificationMessageData;

class HeraldNotificationTraitTest extends TestCase
{
    public function test_mail_builder_renders_default_and_custom_notification_messages(): void
    {
        $user = User::factory()->create([
            'first_name' => 'Ada',
            'last_name' => 'Lovelace',
            'locale' => 'en',
        ]);
        $notification = new BuilderHeraldNotification;

        $mail = $notification->toMail($user);

        $this->assertSame('Invoice INV-1', $mail?->subject);
        $this->assertStringContainsString('Good day Ada,', $mail?->render()->toHtml());
        $this->assertStringContainsString('Hello Ada for INV-1', $mail?->render()->toHtml());

        NotificationMessage::query()->create([
            'notification_class' => $notification::class,
            'locale' => 'en',
            'channel' => GetUserNotificationsAction::CHANNEL_MAIL,
            'subject' => 'Custom :invoice.doc_number',
            'message' => '<p>Custom :first_name for :invoice.doc_number</p>',
        ]);

        $customMail = $notification->toMail($user);

        $this->assertSame('Custom INV-1', $customMail?->subject);
        $this->assertStringContainsString('Good day Ada,', $customMail?->render()->toHtml());
        $this->assertStringContainsString('Custom Ada for INV-1', $customMail?->render()->toHtml());
    }

    public function test_database_builder_renders_payload_without_activating_database_for_email_only_notifiables(): void
    {
        $user = User::factory()->create([
            'first_name' => 'Ada',
            'last_name' => 'Lovelace',
            'locale' => 'en',
        ]);
        $notification = new BuilderHeraldNotification;

        $this->assertSame([
            GetUserNotificationsAction::CHANNEL_MAIL,
        ], $notification->via('ada@example.test'));

        $database = $notification->toDatabase($user);

        $this->assertSame('Database INV-1 for Ada', (string) $database['text']);
        $this->assertSame($user->id, $database['model']->id);
        $this->assertSame('https://example.test/invoices/INV-1', $database['url']);
        $this->assertSame('invoices/INV-1', $database['path']);

        NotificationMessage::query()->create([
            'notification_class' => $notification::class,
            'locale' => 'en',
            'channel' => GetUserNotificationsAction::CHANNEL_DATABASE,
            'subject' => '',
            'message' => 'Custom database :invoice.doc_number for :first_name',
        ]);

        $customDatabase = $notification->toDatabase($user);

        $this->assertSame('Custom database INV-1 for Ada', (string) $customDatabase['text']);
    }

    public function test_database_builder_resolves_me_full_name_variable(): void
    {
        $admin = User::factory()->create([
            'first_name' => 'Admin',
            'last_name' => 'User',
        ]);
        $this->actingAs($admin);

        $recipient = User::factory()->create([
            'first_name' => 'Ada',
            'last_name' => 'Lovelace',
            'locale' => 'en',
        ]);

        $notification = new BuilderHeraldNotification;

        NotificationMessage::query()->create([
            'notification_class' => $notification::class,
            'locale' => 'en',
            'channel' => GetUserNotificationsAction::CHANNEL_DATABASE,
            'subject' => '',
            'message' => '<p>Hello :me.full_name, this is for :first_name</p>',
        ]);

        $database = $notification->toDatabase($recipient);

        $this->assertSame('<p>Hello Admin User, this is for Ada</p>', (string) $database['text']);
    }

    public function test_mail_builder_resolves_me_full_name_after_laravel_queued_notification_serialization(): void
    {
        $admin = User::factory()->create([
            'first_name' => 'Admin',
            'last_name' => 'User',
        ]);
        $this->actingAs($admin);

        $notification = new BuilderHeraldNotification;

        NotificationMessage::query()->create([
            'notification_class' => $notification::class,
            'locale' => 'en',
            'channel' => GetUserNotificationsAction::CHANNEL_MAIL,
            'subject' => 'Queued sender',
            'message' => '<p>Hello :me.full_name, this is for :first_name</p>',
        ]);

        Bus::fake();

        NotificationFacade::send(new HeraldNotifiable(
            full_name: 'Ada Lovelace',
            first_name: 'Ada',
            last_name: 'Lovelace',
            email: 'ada@example.test',
            locale: 'en',
        ), $notification);

        $queuedJob = null;

        Bus::assertDispatched(
            SendQueuedNotifications::class,
            function (SendQueuedNotifications $job) use (&$queuedJob): bool {
                $queuedJob = $job;

                return $job->channels === [GetUserNotificationsAction::CHANNEL_MAIL];
            }
        );

        $this->assertInstanceOf(SendQueuedNotifications::class, $queuedJob);

        Auth::logout();

        $restoredQueuedJob = unserialize(serialize($queuedJob));

        $this->assertInstanceOf(SendQueuedNotifications::class, $restoredQueuedJob);

        $restoredNotification = $restoredQueuedJob->notification;
        $restoredRecipient = $restoredQueuedJob->notifiables->first();

        $this->assertInstanceOf(BuilderHeraldNotification::class, $restoredNotification);
        $this->assertInstanceOf(HeraldNotifiable::class, $restoredRecipient);

        $mail = $restoredNotification->toMail($restoredRecipient);

        $this->assertStringContainsString('Hello Admin User, this is for Ada', $mail?->render()->toHtml());
    }

    public function test_database_builder_resolves_me_full_name_in_preview(): void
    {
        $admin = User::factory()->create([
            'first_name' => 'Admin',
            'last_name' => 'User',
        ]);
        $this->actingAs($admin);

        NotificationMessage::query()->create([
            'notification_class' => BuilderHeraldNotification::class,
            'locale' => 'en',
            'channel' => GetUserNotificationsAction::CHANNEL_DATABASE,
            'subject' => '',
            'message' => '<p>test :me.full_name</p>',
        ]);

        $data = $this->builderHeraldNotificationDetails();
        $previews = $data['previews']['en'] ?? [];

        $this->assertNotEmpty($previews);
        $this->assertStringContainsString('Admin User', $previews[0]['database']->text);
        $this->assertStringNotContainsString(':me.full_name', $previews[0]['database']->text);
    }

    public function test_options_array_includes_supported_channels_and_default_notification_messages(): void
    {
        User::factory()->create();

        $data = $this->builderHeraldNotificationDetails();

        $this->assertSame([
            GetUserNotificationsAction::CHANNEL_MAIL,
            GetUserNotificationsAction::CHANNEL_DATABASE,
        ], $data['supported_channels']);

        $notificationMessages = collect($data['notification_messages']);

        /** @var array<string, string> $locales */
        $locales = config('app.locales');

        foreach (array_keys($locales) as $locale) {
            $mailMessage = $notificationMessages
                ->where('locale', $locale)
                ->where('channel', GetUserNotificationsAction::CHANNEL_MAIL)
                ->first();

            $this->assertNotNull($mailMessage);
            $this->assertFalse($mailMessage->customized);
            $this->assertSame('Invoice :invoice.doc_number', $mailMessage->subject);
            $this->assertSame('<p>Hello :first_name for :invoice.doc_number</p>', $mailMessage->message);
            $this->assertSame('Invoice :invoice.doc_number', $mailMessage->default_subject);
            $this->assertSame('<p>Hello :first_name for :invoice.doc_number</p>', $mailMessage->default_message);

            $databaseMessage = $notificationMessages
                ->where('locale', $locale)
                ->where('channel', GetUserNotificationsAction::CHANNEL_DATABASE)
                ->first();

            $this->assertNotNull($databaseMessage);
            $this->assertFalse($databaseMessage->customized);
            $this->assertSame('', $databaseMessage->subject);
            $this->assertSame('Database :invoice.doc_number for :first_name', $databaseMessage->message);
            $this->assertSame('', $databaseMessage->default_subject);
            $this->assertSame('Database :invoice.doc_number for :first_name', $databaseMessage->default_message);
        }
    }

    public function test_options_array_marks_saved_notification_messages_as_customized(): void
    {
        User::factory()->create();

        NotificationMessage::query()->create([
            'notification_class' => BuilderHeraldNotification::class,
            'locale' => 'en',
            'channel' => GetUserNotificationsAction::CHANNEL_MAIL,
            'subject' => 'Custom :invoice.doc_number',
            'message' => '<p>Custom :first_name for :invoice.doc_number</p>',
        ]);

        $data = $this->builderHeraldNotificationDetails();

        $mailMessage = collect($data['notification_messages'])
            ->where('locale', 'en')
            ->where('channel', GetUserNotificationsAction::CHANNEL_MAIL)
            ->first();

        $this->assertNotNull($mailMessage);
        $this->assertTrue($mailMessage->customized);
        $this->assertSame('Custom :invoice.doc_number', $mailMessage->subject);
        $this->assertSame('<p>Custom :first_name for :invoice.doc_number</p>', $mailMessage->message);
        $this->assertSame('Invoice :invoice.doc_number', $mailMessage->default_subject);
        $this->assertSame('<p>Hello :first_name for :invoice.doc_number</p>', $mailMessage->default_message);
    }

    public function test_options_array_includes_default_previews_when_no_preview_models_exist(): void
    {
        User::factory()->create([
            'first_name' => 'Ada',
            'last_name' => 'Lovelace',
            'locale' => 'en',
        ]);

        $data = $this->builderHeraldNotificationDetails();

        $preview = $data['previews']['en'][0] ?? null;

        $this->assertNotNull($preview);
        $this->assertSame('Builder notification', $preview['title']);
        $this->assertSame('Invoice INV-1', $preview['mail']->subject);
        $this->assertStringContainsString('Good day Ada,', $preview['mail']->html);
        $this->assertStringContainsString('Hello Ada for INV-1', $preview['mail']->html);
        $this->assertSame('Database INV-1 for Ada', $preview['database']->text);
    }

    public function test_database_notification_messages_can_be_saved_without_subjects(): void
    {
        (new UpdateNotificationMessagesAction(new NotificationMessageBatchData([
            new NotificationMessageData(
                notificationClass: BuilderHeraldNotification::class,
                locale: 'en',
                channel: GetUserNotificationsAction::CHANNEL_DATABASE,
                subject: '',
                message: 'Saved database :invoice.doc_number',
                customized: true,
            ),
        ])))->handle();

        $notificationMessage = NotificationMessage::query()
            ->where('notification_class', BuilderHeraldNotification::class)
            ->where('locale', 'en')
            ->where('channel', GetUserNotificationsAction::CHANNEL_DATABASE)
            ->first();

        $this->assertNotNull($notificationMessage);
        $this->assertSame('', $notificationMessage->subject);
        $this->assertSame('Saved database :invoice.doc_number', $notificationMessage->message);
    }

    public function test_uncustomized_notification_messages_are_deleted_in_batch_update(): void
    {
        $notificationMessage = NotificationMessage::query()->create([
            'notification_class' => BuilderHeraldNotification::class,
            'locale' => 'en',
            'channel' => GetUserNotificationsAction::CHANNEL_MAIL,
            'subject' => 'Custom :invoice.doc_number',
            'message' => '<p>Custom :first_name for :invoice.doc_number</p>',
        ]);

        (new UpdateNotificationMessagesAction(new NotificationMessageBatchData([
            new NotificationMessageData(
                notificationClass: BuilderHeraldNotification::class,
                locale: 'en',
                channel: GetUserNotificationsAction::CHANNEL_MAIL,
                subject: '',
                message: '',
                customized: false,
            ),
        ])))->handle();

        $this->assertModelMissing($notificationMessage);
    }

    public function test_faked_sends_to_a_herald_notifiable_are_recorded_under_the_recipient_identity(): void
    {
        NotificationFacade::fake();

        NotificationFacade::send($this->notifiable('Ada', 'Lovelace'), new BuilderHeraldNotification);

        // The fake buckets what it records by key, so a notifiable rebuilt for
        // the assertion has to land on the bucket the send wrote to.
        NotificationFacade::assertSentTo($this->notifiable('Ada', 'Lovelace'), BuilderHeraldNotification::class);
        NotificationFacade::assertNotSentTo($this->notifiable('Grace', 'Hopper'), BuilderHeraldNotification::class);
    }

    public function test_a_notifiable_without_timezone_takes_the_host_timezone(): void
    {
        config()->set('notifications.timezone', 'Europe/Paris');
        Herald::forgetResolvers();

        $this->assertSame('Europe/Paris', $this->notifiable('Ada', 'Lovelace')->timezone);

        Herald::resolveTimezoneUsing(fn (): string => 'America/Vancouver');

        $this->assertSame('America/Vancouver', $this->notifiable('Ada', 'Lovelace')->timezone);
        $this->assertSame('Asia/Tokyo', (new HeraldNotifiable('Ada Lovelace', 'Ada', 'Lovelace', 'ada@example.test', timezone: 'Asia/Tokyo'))->timezone);

        Herald::forgetResolvers();
    }

    private function notifiable(string $firstName, string $lastName): HeraldNotifiable
    {
        return new HeraldNotifiable(
            full_name: $firstName . ' ' . $lastName,
            first_name: $firstName,
            last_name: $lastName,
            email: strtolower($firstName) . '@example.test',
            locale: 'en',
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function builderHeraldNotificationDetails(): array
    {
        return HeraldNotificationDetailsData::fromOptions(
            key: 'builder_herald_notification',
            heraldOptions: BuilderHeraldNotification::herald(),
        )->toArray();
    }
}
