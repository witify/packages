<?php

namespace Witify\Notifications\Herald;

use Closure;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Throwable;
use Witify\Notifications\Actions\GetUserNotificationsAction;
use Witify\Notifications\Models\NotificationMessage;
use Witify\Support\Host;
use Witify\Support\NotificationChannels\DatabaseChannel;

class HeraldOptions
{
    public string $notificationClass;

    public string $title = '';

    public string $description = '';

    public string $group = '';

    public bool $toggleable = true;

    public bool $customizable = true;

    public ?Closure $variables = null;

    public ?Closure $mail = null;

    public ?Closure $database = null;

    public ?Closure $previews = null;

    public bool $throttle = false;

    public ?Closure $uniqueKey = null;

    private ?Closure $mailMessage = null;

    private ?Closure $attachments = null;

    private ?Closure $schedule = null;

    public function __construct(string $notificationClass)
    {
        $this->notificationClass = $notificationClass;
    }

    public static function make(string $notificationClass): self
    {
        return new self($notificationClass);
    }

    /*
    |--------------------------------------------------------------------------
    | Configuration
    |--------------------------------------------------------------------------
    */

    public function title(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function description(string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function group(string $group): self
    {
        $this->group = $group;

        return $this;
    }

    /**
     * Whether the notification can be toggled on/off in user account settings.
     */
    public function toggleable(bool $toggleable): self
    {
        $this->toggleable = $toggleable;

        return $this;
    }

    /**
     * Whether the notification content can be customized by admins.
     */
    public function customizable(bool $customizable): self
    {
        $this->customizable = $customizable;

        return $this;
    }

    /**
     * Set the variables for the notification.
     */
    public function variables(Closure $variables): self
    {
        $this->variables = $variables;

        return $this;
    }

    public function mail(Closure $mail): self
    {
        $this->mail = $mail;

        return $this;
    }

    public function database(Closure $database): self
    {
        $this->database = $database;

        return $this;
    }

    public function previews(Closure $previews): self
    {
        $this->previews = $previews;

        return $this;
    }

    public function throttle(bool $throttle = true): self
    {
        $this->throttle = $throttle;

        return $this;
    }

    public function uniqueKey(Closure $uniqueKey): self
    {
        $this->uniqueKey = $uniqueKey;

        return $this;
    }

    public function mailMessage(Closure $mailMessage): self
    {
        $this->mailMessage = $mailMessage;

        return $this;
    }

    public function attachments(Closure $attachments): self
    {
        $this->attachments = $attachments;

        return $this;
    }

    /**
     * @param  Closure(HeraldOptionsSchedule): HeraldOptionsSchedule  $schedule
     */
    public function schedule(Closure $schedule): self
    {
        $this->schedule = $schedule;

        return $this;
    }

    /*
    |--------------------------------------------------------------------------
    | Getters
    |--------------------------------------------------------------------------
    */

    /**
     * Get the variables for the notification.
     *
     * @return array<string, mixed>
     */
    private function getVariables(?HeraldNotification $notification, HeraldNotifiable $notifiable): array
    {
        $variables = [];

        if ($this->variables) {
            $resolvedVariables = ($this->variables)($notification, $notifiable);
            $variables = is_array($resolvedVariables) ? $resolvedVariables : [];
        }

        $senderData = $notification?->getHeraldSenderData() ?? $this->getAuthenticatedSenderData();

        return array_merge($variables, [
            'full_name' => $notifiable->full_name ?? null,
            'first_name' => $notifiable->first_name ?? null,
            'last_name' => $notifiable->last_name ?? null,
            'email' => $notifiable->email ?? null,
            'company_name' => Host::companyName(),
            'me' => $senderData,
        ]);
    }

    /**
     * @return array{full_name: string|null, first_name: string|null, last_name: string|null, email: string|null}
     */
    private function getAuthenticatedSenderData(): array
    {
        $user = Auth::user();

        if (! $user instanceof IsHeraldNotifiable) {
            return [
                'full_name' => null,
                'first_name' => null,
                'last_name' => null,
                'email' => null,
            ];
        }

        $sender = $user->heraldNotifiable();

        return [
            'full_name' => $sender->full_name,
            'first_name' => $sender->first_name,
            'last_name' => $sender->last_name,
            'email' => $sender->email,
        ];
    }

    /**
     * Get the mail message for the notification.
     */
    public function getMail(HeraldNotification $notification, HeraldNotifiable $notifiable): ?MailMessage
    {
        if (! $this->supportsMail()) {
            return null;
        }

        app()->setLocale($notifiable->locale ?? config('app.locale'));

        $mailMessage = $this->buildMailMessage($notification, $notifiable);

        if (! $mailMessage) {
            if ($this->mailMessage) {
                $mailMessage = ($this->mailMessage)($notification, $mailMessage);
            }
        }

        if ($mailMessage && $this->attachments) {
            $attachments = ($this->getAttachments($notification, $notifiable));

            foreach ($attachments as $attachment) {
                $mailMessage->attach($attachment);
            }
        }

        return $mailMessage;
    }

    /**
     * @return array{text?: mixed, model?: mixed, url?: string|null, path?: string|null}
     */
    public function getDatabase(HeraldNotification $notification, HeraldNotifiable $notifiable): array
    {
        if (! $this->supportsDatabase()) {
            return [];
        }

        app()->setLocale($notifiable->locale ?? config('app.locale'));

        $variables = $this->getVariables($notification, $notifiable);
        $notificationMessage = $this->getNotificationMessageForChannel($notification, GetUserNotificationsAction::CHANNEL_DATABASE);
        $databaseMessage = $this->buildNotificationMessage($this->database, $notification, $notifiable, $variables);

        if ($databaseMessage instanceof NotificationMessageBuilder) {
            if ($notificationMessage) {
                $databaseMessage->notificationMessage($notificationMessage);
            }

            return $databaseMessage->toDatabase($variables);
        }

        if ($notificationMessage) {
            return NotificationMessageBuilder::database()
                ->notificationMessage($notificationMessage)
                ->toDatabase($variables);
        }

        if (is_array($databaseMessage)) {
            return $databaseMessage;
        }

        return [];
    }

    /**
     * @return array<int, mixed>
     */
    private function getAttachments(HeraldNotification $notification, HeraldNotifiable $notifiable): array
    {
        if ($this->attachments) {
            $attachments = ($this->attachments)($notification, $notifiable);

            return is_array($attachments) ? array_values($attachments) : [];
        }

        return [];
    }

    /**
     * Build the mail message for the notification.
     */
    private function buildMailMessage(HeraldNotification $notification, HeraldNotifiable $notifiable): ?MailMessage
    {
        $variables = $this->getVariables($notification, $notifiable);
        $notificationMessage = $this->getNotificationMessageForChannel($notification, GetUserNotificationsAction::CHANNEL_MAIL);
        $mailMessage = $this->buildNotificationMessage($this->mail, $notification, $notifiable, $variables);

        if ($mailMessage instanceof NotificationMessageBuilder) {
            if ($notificationMessage) {
                $mailMessage->notificationMessage($notificationMessage);
            }

            return $mailMessage->toMailMessage($variables, $notifiable);
        }

        if ($notificationMessage) {
            return NotificationMessageBuilder::mail()
                ->notificationMessage($notificationMessage)
                ->toMailMessage($variables, $notifiable);
        }

        if ($mailMessage instanceof MailMessage) {
            return $mailMessage;
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $variables
     */
    private function buildNotificationMessage(?Closure $closure, ?HeraldNotification $notification, HeraldNotifiable $notifiable, array $variables): mixed
    {
        if (! $closure) {
            return null;
        }

        return $closure($notification, $notifiable, $variables);
    }

    private function getNotificationMessageForChannel(HeraldNotification $notification, string $channel): ?NotificationMessage
    {
        $customNotificationMessages = $notification->getCustomNotificationMessages();

        if ($customNotificationMessages) {
            $customNotificationMessage = collect($customNotificationMessages)
                ->where('locale', app()->getLocale())
                ->where('channel', $channel)
                ->first();

            if ($customNotificationMessage) {
                return $customNotificationMessage;
            }
        }

        return $this->getStoredNotificationMessageForChannel($channel);
    }

    private function getStoredNotificationMessageForChannel(string $channel): ?NotificationMessage
    {
        if (! $this->customizable) {
            return null;
        }

        return NotificationMessage::query()
            ->where('notification_class', $this->notificationClass)
            ->where('locale', app()->getLocale())
            ->where('channel', $channel)
            ->first();
    }

    private function previewNotifiable(string $locale): HeraldNotifiable
    {
        $user = Herald::userModel()::query()->first();

        if ($user instanceof IsHeraldNotifiable) {
            $user->setAttribute('locale', $locale);

            return $user->heraldNotifiable();
        }

        return new HeraldNotifiable(
            full_name: 'John Doe',
            first_name: 'John',
            last_name: 'Doe',
            email: 'john.doe@example.com',
            locale: $locale,
        );
    }

    /**
     * @return array<int, array{title: string, mail: MailPreview|null, database: DatabasePreview|null}>
     */
    public function getPreviews(string $locale): array
    {
        $oldLocale = app()->getLocale();

        $notifiable = $this->previewNotifiable($locale);
        app()->setLocale($locale);

        try {
            $data = collect($this->notificationPreviews())
                ->map(function (NotificationPreview $preview) use ($notifiable) {

                    $notification = $preview->notification;

                    return [
                        'title' => $preview->title,
                        'mail' => $this->supportsMail() ? $notification->toMailPreview($notifiable) : null,
                        'database' => $this->supportsDatabase() ? $notification->toDatabasePreview($notifiable) : null,
                    ];
                })
                ->values()
                ->toArray();

            if ($data === []) {
                return $this->getDefaultPreviews($notifiable);
            }

            return $data;
        } finally {
            app()->setLocale($oldLocale);
        }
    }

    /**
     * @return array<int, NotificationPreview>
     */
    private function notificationPreviews(): array
    {
        if (! $this->previews) {
            return [];
        }

        $previews = ($this->previews)();

        if (! is_array($previews)) {
            return [];
        }

        return array_values(array_filter(
            $previews,
            fn (mixed $preview): bool => $preview instanceof NotificationPreview,
        ));
    }

    /**
     * @return array<int, array{title: string, mail: MailPreview|null, database: DatabasePreview|null}>
     */
    private function getDefaultPreviews(HeraldNotifiable $notifiable): array
    {
        $variables = $this->getVariables(null, $notifiable);
        $mail = $this->supportsMail() ? $this->getDefaultMailPreview($notifiable, $variables) : null;
        $database = $this->supportsDatabase() ? $this->getDefaultDatabasePreview($notifiable, $variables) : null;

        if ($mail === null && $database === null) {
            return [];
        }

        return [[
            'title' => $this->title,
            'mail' => $mail,
            'database' => $database,
        ]];
    }

    /**
     * @param  array<string, mixed>  $variables
     */
    private function getDefaultMailPreview(HeraldNotifiable $notifiable, array $variables): ?MailPreview
    {
        try {
            $notificationMessage = $this->getStoredNotificationMessageForChannel(GetUserNotificationsAction::CHANNEL_MAIL);
            $mailMessage = $this->buildNotificationMessage($this->mail, null, $notifiable, $variables);

            if ($mailMessage instanceof NotificationMessageBuilder) {
                if ($notificationMessage) {
                    $mailMessage->notificationMessage($notificationMessage);
                }

                return $this->mailMessageToPreview($mailMessage->toMailMessage($variables, $notifiable));
            }

            if ($notificationMessage) {
                return $this->mailMessageToPreview(
                    NotificationMessageBuilder::mail()
                        ->notificationMessage($notificationMessage)
                        ->toMailMessage($variables, $notifiable)
                );
            }

            if ($mailMessage instanceof MailMessage) {
                return $this->mailMessageToPreview($mailMessage);
            }
        } catch (Throwable) {
            return null;
        }

        return null;
    }

    private function mailMessageToPreview(MailMessage $mail): MailPreview
    {
        // @phpstan-ignore nullCoalesce.property
        $subject = $mail->subject ?? '';

        return new MailPreview($subject, $mail->render()->toHtml());
    }

    /**
     * @param  array<string, mixed>  $variables
     */
    private function getDefaultDatabasePreview(HeraldNotifiable $notifiable, array $variables): ?DatabasePreview
    {
        try {
            $notificationMessage = $this->getStoredNotificationMessageForChannel(GetUserNotificationsAction::CHANNEL_DATABASE);
            $databaseMessage = $this->buildNotificationMessage($this->database, null, $notifiable, $variables);

            if ($databaseMessage instanceof NotificationMessageBuilder) {
                if ($notificationMessage) {
                    $databaseMessage->notificationMessage($notificationMessage);
                }

                return $this->databaseArrayToPreview($databaseMessage->toDatabase($variables));
            }

            if ($notificationMessage) {
                return $this->databaseArrayToPreview(
                    NotificationMessageBuilder::database()
                        ->notificationMessage($notificationMessage)
                        ->toDatabase($variables)
                );
            }

            if (is_array($databaseMessage)) {
                return $this->databaseArrayToPreview($databaseMessage);
            }
        } catch (Throwable) {
            return null;
        }

        return null;
    }

    /**
     * @param  array{text?: mixed, model?: mixed, url?: string|null, path?: string|null}  $database
     */
    private function databaseArrayToPreview(array $database): DatabasePreview
    {
        return new DatabasePreview(
            text: (string) ($database['text'] ?? ''),
            url: $database['url'] ?? null,
            path: $database['path'] ?? null,
        );
    }

    /**
     * @return array<int, string>
     */
    public function getVia(HeraldNotification $notification, mixed $notifiable): array
    {
        $user = null;

        if ($notifiable instanceof HeraldUser) {
            $user = $notifiable;
        } elseif ($notifiable instanceof HeraldNotifiable && $notifiable->user instanceof HeraldUser) {
            $user = $notifiable->user;
        }

        $channels = [];

        if ($this->supportsMail()) {
            $channels[] = GetUserNotificationsAction::CHANNEL_MAIL;
        }

        if ($this->supportsDatabase() && $user) {
            $channels[] = DatabaseChannel::class;
        }

        if (! $user) {
            return $channels;
        }

        $enabledChannels = (new GetUserNotificationsAction($user))->enabledChannels($this->notificationClass);

        $channels = [];

        if ($this->supportsMail() && in_array(GetUserNotificationsAction::CHANNEL_MAIL, $enabledChannels, true)) {
            $channels[] = GetUserNotificationsAction::CHANNEL_MAIL;
        }

        if (
            $this->supportsDatabase()
            && in_array(GetUserNotificationsAction::CHANNEL_DATABASE, $enabledChannels, true)
        ) {
            $channels[] = DatabaseChannel::class;
        }

        if ($channels === []) {
            return [];
        }

        if (! $this->throttle) {
            return $channels;
        }

        $key = $this->cacheKey($notification, $user);

        if (RateLimiter::tooManyAttempts($key, $perDay = 1)) {
            return [];
        }

        RateLimiter::increment($key);

        return $channels;
    }

    public function supportsMail(): bool
    {
        return $this->mail !== null || $this->mailMessage !== null;
    }

    public function supportsDatabase(): bool
    {
        return $this->database !== null;
    }

    /**
     * @return array<int, string>
     */
    public function supportedChannels(): array
    {
        $channels = [];

        if ($this->supportsMail()) {
            $channels[] = GetUserNotificationsAction::CHANNEL_MAIL;
        }

        if ($this->supportsDatabase()) {
            $channels[] = GetUserNotificationsAction::CHANNEL_DATABASE;
        }

        return $channels;
    }

    /*
    |--------------------------------------------------------------------------
    | Utils
    |--------------------------------------------------------------------------
    */

    private function cacheKey(HeraldNotification $notification, HeraldUser $user): string
    {
        $uniqueKey = $this->uniqueKey;

        $uniqueKeyValue = '';

        if (is_callable($uniqueKey)) {
            $uniqueKeyValue = $uniqueKey($notification);
        }

        return 'notification:' . get_class() . ':' . $user->heraldNotifiable()->id . ':' . $uniqueKeyValue;
    }

    /*
    |--------------------------------------------------------------------------
    | Schedule
    |--------------------------------------------------------------------------
    */

    public function getSchedule(): ?HeraldOptionsSchedule
    {
        $schedule = $this->schedule;

        if ($schedule === null) {
            return null;
        }

        return $schedule(new HeraldOptionsSchedule);
    }

    /**
     * @return Collection<int, NotificationMessage>
     */
    public function getNotificationMessages(): Collection
    {
        $supportedChannels = $this->supportedChannels();

        $storedNotificationMessages = NotificationMessage::query()
            ->where('notification_class', $this->notificationClass)
            ->whereIn('channel', $supportedChannels)
            ->get()
            ->keyBy(fn (NotificationMessage $message): string => $this->notificationMessageKey($message));

        $notificationMessages = collect();

        foreach ($supportedChannels as $channel) {
            foreach ($this->getDefaultNotificationMessagesForChannel($channel) as $message) {
                /** @var NotificationMessage|null $storedNotificationMessage */
                $storedNotificationMessage = $storedNotificationMessages->pull($this->notificationMessageKey($message));

                $notificationMessages->push(
                    $this->markNotificationMessageForCustomization(
                        $storedNotificationMessage ?? $message,
                        customized: $storedNotificationMessage !== null,
                        defaultNotificationMessage: $message,
                    )
                );
            }
        }

        $storedNotificationMessages->each(function (NotificationMessage $message) use ($notificationMessages): void {
            $notificationMessages->push(
                $this->markNotificationMessageForCustomization($message, customized: true)
            );
        });

        /** @var Collection<int, NotificationMessage> $notificationMessages */
        return $notificationMessages;
    }

    /**
     * @return array<string, mixed>
     */
    public function getPreviewVariables(): array
    {
        return $this->getVariables(null, new HeraldNotifiable(
            full_name: 'John Doe',
            first_name: 'John',
            last_name: 'Doe',
            email: 'john.doe@example.com',
        ));
    }

    /**
     * @return array<string, mixed>
     */
    public function getPreviewData(): array
    {
        $previews = [];

        foreach (Herald::locales() as $code) {

            $notificationPreviews = $this->getPreviews($code);

            $previews[$code] = $notificationPreviews;
        }

        return $previews;
    }

    /**
     * @return NotificationMessage[]
     */
    private function getDefaultNotificationMessagesForChannel(string $channel): array
    {
        if (! $this->customizable) {
            return [];
        }

        $closure = match ($channel) {
            GetUserNotificationsAction::CHANNEL_MAIL => $this->mail,
            GetUserNotificationsAction::CHANNEL_DATABASE => $this->database,
            default => null,
        };

        if (! $closure) {
            return [];
        }

        $oldLocale = app()->getLocale();
        $messages = [];

        try {
            foreach (Herald::locales() as $locale) {
                app()->setLocale($locale);

                $notifiable = new HeraldNotifiable(
                    full_name: 'John Doe',
                    first_name: 'John',
                    last_name: 'Doe',
                    email: 'john.doe@example.com',
                    locale: $locale,
                );
                $variables = $this->getVariables(null, $notifiable);
                $message = $closure(null, $notifiable, $variables);

                if (! $message instanceof NotificationMessageBuilder) {
                    continue;
                }

                $messages[] = $message->toNotificationMessage($this->notificationClass, $locale);
            }
        } finally {
            app()->setLocale($oldLocale);
        }

        return $messages;
    }

    private function notificationMessageKey(NotificationMessage $notificationMessage): string
    {
        return "{$notificationMessage->channel}.{$notificationMessage->locale}";
    }

    private function markNotificationMessageForCustomization(
        NotificationMessage $notificationMessage,
        bool $customized,
        ?NotificationMessage $defaultNotificationMessage = null,
    ): NotificationMessage {
        $defaultSubject = $notificationMessage->subject;
        $defaultMessage = $notificationMessage->message;

        if ($defaultNotificationMessage !== null) {
            $defaultSubject = $defaultNotificationMessage->subject;
            $defaultMessage = $defaultNotificationMessage->message;
        }

        $notificationMessage->setAttribute('customized', $customized);
        $notificationMessage->setAttribute('default_subject', $defaultSubject);
        $notificationMessage->setAttribute('default_message', $defaultMessage);

        return $notificationMessage;
    }
}
