<?php

namespace Witify\Notifications\Herald;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Auth;
use Witify\Notifications\Models\NotificationMessage;

trait HeraldNotificationTrait
{
    use Queueable;

    /** @var NotificationMessage[] */
    protected array $customNotificationMessages = [];

    /** @var array{full_name: string|null, first_name: string|null, last_name: string|null, email: string|null}|null */
    protected ?array $heraldSenderData = null;

    /**
     * Set custom notification messages.
     *
     * @param  NotificationMessage[]  $messages
     */
    public function setCustomNotificationMessages(array $messages): void
    {
        $this->customNotificationMessages = $messages;
    }

    /**
     * @return NotificationMessage[]
     */
    public function getCustomNotificationMessages(): array
    {
        return $this->customNotificationMessages;
    }

    /**
     * @return array{full_name: string|null, first_name: string|null, last_name: string|null, email: string|null}|null
     */
    public function getHeraldSenderData(): ?array
    {
        return $this->heraldSenderData;
    }

    private function captureHeraldSenderData(): void
    {
        if ($this->heraldSenderData !== null) {
            return;
        }

        $user = Auth::user();

        if (! $user instanceof IsHeraldNotifiable) {
            return;
        }

        $sender = $user->heraldNotifiable();

        $this->heraldSenderData = [
            'full_name' => $sender->full_name,
            'first_name' => $sender->first_name,
            'last_name' => $sender->last_name,
            'email' => $sender->email,
        ];
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(mixed $notifiable): array
    {
        $this->captureHeraldSenderData();

        /** @var HeraldOptions $heraldOptions */
        $heraldOptions = self::herald();

        return $heraldOptions->getVia($this, $notifiable);
    }

    public function toMail(mixed $notifiable): ?MailMessage
    {
        /** @var HeraldOptions $heraldOptions */
        $heraldOptions = self::herald();

        return $heraldOptions->getMail($this, $this->resolveHeraldNotifiable($notifiable));
    }

    public function toMailPreview(mixed $notifiable): MailPreview
    {
        $mail = $this->toMail($notifiable);

        if (! $mail) {
            return new MailPreview('', '');
        }

        // @phpstan-ignore nullCoalesce.property
        $subject = $mail->subject ?? '';
        $html = $mail->render()->toHtml();

        return new MailPreview($subject, $html);
    }

    /**
     * @return array{text?: mixed, model?: mixed, url?: string|null, path?: string|null}
     */
    public function toDatabase(mixed $notifiable): array
    {
        /** @var HeraldOptions $heraldOptions */
        $heraldOptions = self::herald();

        return $heraldOptions->getDatabase($this, $this->resolveHeraldNotifiable($notifiable));
    }

    public function toDatabasePreview(mixed $notifiable): DatabasePreview
    {
        $database = $this->toDatabase($notifiable);

        return new DatabasePreview(
            text: (string) ($database['text'] ?? ''),
            url: $database['url'] ?? null,
            path: $database['path'] ?? null,
        );
    }

    private function resolveHeraldNotifiable(mixed $notifiable): HeraldNotifiable
    {
        if ($notifiable instanceof HeraldNotifiable) {
            return $notifiable;
        }

        if (is_array($notifiable)) {
            return new HeraldNotifiable(
                full_name: $notifiable['full_name'] ?? '',
                first_name: $notifiable['first_name'] ?? '',
                last_name: $notifiable['last_name'] ?? '',
                email: $notifiable['email'] ?? '',
                phone: $notifiable['phone'] ?? null,
                locale: $notifiable['locale'] ?? 'en',
            );
        }

        if (is_string($notifiable)) {
            return new HeraldNotifiable(
                full_name: '',
                first_name: '',
                last_name: '',
                email: $notifiable,
                locale: 'en',
            );
        }

        if ($notifiable instanceof IsHeraldNotifiable) {
            return $notifiable->heraldNotifiable();
        }

        throw new \Exception('Notifiable must be an instance of HeraldNotifiable, an array or a string.');
    }
}
