<?php

namespace Witify\Notifications\Herald;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Arr;
use Illuminate\Support\HtmlString;
use Stringable;
use Witify\Notifications\Actions\GetUserNotificationsAction;
use Witify\Notifications\Models\NotificationMessage;
use Witify\Support\Mail\MailMessage as SupportMailMessage;

class NotificationMessageBuilder
{
    private ?string $subject = null;

    private string $message = '';

    private ?string $actionText = null;

    private ?string $actionUrl = null;

    private ?Model $model = null;

    private ?string $url = null;

    private ?string $path = null;

    public function __construct(
        private string $channel,
    ) {}

    public static function mail(): self
    {
        return new self(GetUserNotificationsAction::CHANNEL_MAIL);
    }

    public static function database(): self
    {
        return new self(GetUserNotificationsAction::CHANNEL_DATABASE);
    }

    public function subject(string $subject): self
    {
        $this->subject = $subject;

        return $this;
    }

    public function message(string|HtmlString $message): self
    {
        $this->message = $this->stringValue($message);

        return $this;
    }

    public function action(string $text, string $url): self
    {
        $this->actionText = $text;
        $this->actionUrl = $url;

        return $this;
    }

    public function model(?Model $model): self
    {
        $this->model = $model;

        return $this;
    }

    public function url(?string $url): self
    {
        $this->url = $url;

        return $this;
    }

    public function path(?string $path): self
    {
        $this->path = $path;

        return $this;
    }

    public function notificationMessage(NotificationMessage $notificationMessage): self
    {
        $this->subject = $notificationMessage->subject;
        $this->message = $notificationMessage->message;

        return $this;
    }

    /**
     * @param  array<string, mixed>  $variables
     */
    public function toMailMessage(array $variables, HeraldNotifiable $notifiable): MailMessage
    {
        $mail = (new SupportMailMessage($notifiable))
            ->subject($this->render($this->subject ?? '', $variables))
            ->line(new HtmlString($this->render($this->message, $variables)));

        if ($this->actionText && $this->actionUrl) {
            $mail->action(
                $this->render($this->actionText, $variables),
                $this->render($this->actionUrl, $variables),
            );
        }

        return $mail;
    }

    /**
     * @param  array<string, mixed>  $variables
     * @return array{text: HtmlString, model?: Model|null, url?: string|null, path?: string|null}
     */
    public function toDatabase(array $variables): array
    {
        return array_filter([
            'text' => new HtmlString($this->render($this->message, $variables)),
            'model' => $this->model,
            'url' => $this->renderNullable($this->url, $variables),
            'path' => $this->renderNullable($this->path, $variables),
        ], fn (mixed $value): bool => $value !== null && $value !== '');
    }

    public function toNotificationMessage(string $notificationClass, string $locale): NotificationMessage
    {
        return new NotificationMessage([
            'notification_class' => $notificationClass,
            'locale' => $locale,
            'channel' => $this->channel,
            'subject' => $this->subject ?? '',
            'message' => $this->message,
        ]);
    }

    /**
     * @param  array<string, mixed>  $variables
     */
    private function render(string $template, array $variables): string
    {
        return preg_replace_callback(
            '/:([a-zA-Z0-9_]+\.[a-zA-Z0-9_.]*[a-zA-Z0-9_]+|[a-zA-Z0-9_]+)/',
            function (array $matches) use ($variables): string {
                $value = Arr::get($variables, $matches[1]);

                if (! is_scalar($value) && ! $value instanceof Stringable) {
                    return '';
                }

                try {
                    return is_string($value) ? $value : strval($value);
                } catch (\Throwable) {
                    return '';
                }
            },
            $template,
        ) ?? $template;
    }

    /**
     * @param  array<string, mixed>  $variables
     */
    private function renderNullable(?string $template, array $variables): ?string
    {
        if ($template === null) {
            return null;
        }

        return $this->render($template, $variables);
    }

    private function stringValue(string|HtmlString $value): string
    {
        if ($value instanceof HtmlString) {
            return $value->toHtml();
        }

        return $value;
    }
}
