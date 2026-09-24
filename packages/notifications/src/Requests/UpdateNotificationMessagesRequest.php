<?php

namespace Witify\Notifications\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;
use Witify\Notifications\Actions\GetUserNotificationsAction;
use Witify\Notifications\Herald\Herald;
use Witify\Notifications\Herald\HeraldNotification;
use Witify\Notifications\ValueObjects\NotificationMessageBatchData;
use Witify\Notifications\ValueObjects\NotificationMessageData;

class UpdateNotificationMessagesRequest extends FormRequest
{
    /**
     * The admin routes carry the middleware of notifications.routes.admin_middleware.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'notification_messages' => ['required', 'array', 'list'],
            'notification_messages.*' => [
                'required',
                'array:notification_class,locale,channel,subject,message,customized',
            ],
            'notification_messages.*.notification_class' => [
                'required',
                'string',
                Rule::in(Herald::classes()),
            ],
            'notification_messages.*.locale' => [
                'required',
                'string',
                Rule::in(Herald::locales()),
            ],
            'notification_messages.*.channel' => [
                'required',
                'string',
                Rule::in(GetUserNotificationsAction::CHANNELS),
            ],
            'notification_messages.*.subject' => ['sometimes', 'nullable', 'string'],
            'notification_messages.*.message' => ['sometimes', 'nullable', 'string'],
            'notification_messages.*.customized' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $messages = $this->input('notification_messages', []);

                if (! is_array($messages)) {
                    return;
                }

                foreach ($messages as $index => $message) {
                    if (! is_array($message)) {
                        continue;
                    }

                    $this->validateNotificationMessage($validator, (int) $index, $message);
                }
            },
        ];
    }

    public function notificationMessages(): NotificationMessageBatchData
    {
        /** @var array<int, array{notification_class: string, locale: string, channel: string, subject?: string|null, message?: string|null, customized?: bool|int|string}> $messages */
        $messages = $this->validated('notification_messages');

        return new NotificationMessageBatchData(
            messages: collect($messages)
                ->map(fn (array $message): NotificationMessageData => new NotificationMessageData(
                    notificationClass: $message['notification_class'],
                    locale: $message['locale'],
                    channel: $message['channel'],
                    subject: $message['subject'] ?? '',
                    message: $message['message'] ?? '',
                    customized: filter_var($message['customized'] ?? true, FILTER_VALIDATE_BOOLEAN),
                ))
                ->values()
                ->all(),
        );
    }

    /**
     * @param  array<string, mixed>  $message
     */
    private function validateNotificationMessage(Validator $validator, int $index, array $message): void
    {
        $notificationClass = (string) ($message['notification_class'] ?? '');
        $channel = (string) ($message['channel'] ?? '');
        $prefix = "notification_messages.{$index}";

        if (Herald::keyOf($notificationClass) === null) {
            return;
        }

        /** @var class-string<HeraldNotification> $notificationClass */
        $herald = $notificationClass::herald();

        if (! $herald->customizable) {
            $this->addInvalidError($validator, "{$prefix}.notification_class");

            return;
        }

        if (! in_array($channel, $herald->supportedChannels(), true)) {
            $this->addInvalidError($validator, "{$prefix}.channel");
        }

        if (! filter_var($message['customized'] ?? true, FILTER_VALIDATE_BOOLEAN)) {
            return;
        }

        if (blank($message['message'] ?? null)) {
            $this->addRequiredError($validator, "{$prefix}.message");
        }

        if ($channel === GetUserNotificationsAction::CHANNEL_MAIL && blank($message['subject'] ?? null)) {
            $this->addRequiredError($validator, "{$prefix}.subject");
        }
    }

    private function addInvalidError(Validator $validator, string $attribute): void
    {
        $validator->errors()->add($attribute, __('validation.in', ['attribute' => $attribute]));
    }

    private function addRequiredError(Validator $validator, string $attribute): void
    {
        $validator->errors()->add($attribute, __('validation.required', ['attribute' => $attribute]));
    }
}
