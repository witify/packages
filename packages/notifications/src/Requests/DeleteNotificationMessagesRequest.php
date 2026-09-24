<?php

namespace Witify\Notifications\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;
use Witify\Notifications\Actions\GetUserNotificationsAction;
use Witify\Notifications\Herald\Herald;
use Witify\Notifications\Herald\HeraldNotification;

class DeleteNotificationMessagesRequest extends FormRequest
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
            'notification_class' => ['required', 'string', Rule::in(Herald::classes())],
            'channel' => ['sometimes', 'string', Rule::in(GetUserNotificationsAction::CHANNELS)],
        ];
    }

    /**
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $notificationClass = (string) $this->input('notification_class');

                if (Herald::keyOf($notificationClass) === null) {
                    return;
                }

                /** @var class-string<HeraldNotification> $notificationClass */
                $herald = $notificationClass::herald();

                if (! $herald->customizable) {
                    $validator->errors()->add(
                        'notification_class',
                        __('validation.in', ['attribute' => 'notification_class']),
                    );

                    return;
                }

                $channel = $this->input('channel');

                if (is_string($channel) && ! in_array($channel, $herald->supportedChannels(), true)) {
                    $validator->errors()->add(
                        'channel',
                        __('validation.in', ['attribute' => 'channel']),
                    );
                }
            },
        ];
    }

    public function notificationClass(): string
    {
        return (string) $this->validated('notification_class');
    }

    public function channel(): ?string
    {
        $channel = $this->validated('channel');

        return is_string($channel) ? $channel : null;
    }
}
