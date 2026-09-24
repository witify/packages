<?php

namespace Witify\Notifications\Herald;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Witify\Notifications\Models\NotificationMessage;

interface HeraldNotification extends ShouldQueue
{
    public static function herald(): HeraldOptions;

    /**
     * @param  array<int|string, NotificationMessage>  $messages
     */
    public function setCustomNotificationMessages(array $messages): void;

    /**
     * @return array<int|string, NotificationMessage>
     */
    public function getCustomNotificationMessages(): array;

    /**
     * @return array<int, string>
     */
    public function via(mixed $notifiable): array;

    public function toMail(mixed $notifiable): ?MailMessage;

    public function toMailPreview(mixed $notifiable): MailPreview;

    /**
     * @return array{text?: mixed, model?: mixed, url?: string|null, path?: string|null}
     */
    public function toDatabase(mixed $notifiable): array;

    public function toDatabasePreview(mixed $notifiable): DatabasePreview;

    /**
     * @return array{full_name: string|null, first_name: string|null, last_name: string|null, email: string|null}|null
     */
    public function getHeraldSenderData(): ?array;
}
