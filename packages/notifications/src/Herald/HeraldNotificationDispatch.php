<?php

namespace Witify\Notifications\Herald;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Witify\Notifications\Actions\BuildHeraldNotificationDetailsAction;
use Witify\Notifications\Models\NotificationMessage;

class HeraldNotificationDispatch
{
    protected HeraldNotification $notification;

    /** @var array<int, HeraldNotifiable> */
    protected array $notifiables;

    public function notification(HeraldNotification $notification): self
    {
        $this->notification = $notification;

        return $this;
    }

    /**
     * @param  array<int, HeraldNotifiable>  $notifiables
     */
    public function notifiables(array $notifiables): self
    {
        $this->notifiables = $notifiables;

        return $this;
    }

    public function responseFromRequest(Request $request): JsonResponse
    {
        $notificationMessages = collect((array) $request->get('notification_messages', []))
            ->map(function ($item) {
                return new NotificationMessage([
                    'locale' => $item['locale'] ?? app()->getLocale(),
                    'channel' => $item['channel'] ?? NotificationMessage::CHANNEL_MAIL,
                    'subject' => $item['subject'] ?? '',
                    'message' => $item['message'] ?? '',
                ]);
            })
            ->all();

        $this->notification->setCustomNotificationMessages($notificationMessages);

        if ($request->action == 'preview') {
            return $this->preview();
        }

        return $this->send();
    }

    private function preview(): JsonResponse
    {
        $notifiablesByLocale = collect($this->notifiables)->groupBy('locale');

        return response()->json([
            'data' => [
                'herald_notification' => (new BuildHeraldNotificationDetailsAction($this->notification::class))->handle(),
                'notifiables' => collect($this->notifiables)->sortBy('full_name')->values(),
                'previews' => $notifiablesByLocale
                    ->map(function ($notifiables) {
                        return $this->notification->toMailPreview($notifiables->first());
                    }),
            ],
        ]);
    }

    private function send(): JsonResponse
    {
        $selectedNotifiables = collect($this->notifiables)
            ->when(
                request()->get('notifiable_ids'),
                function ($notifiables) {
                    return $notifiables->whereIn('id', request()->get('notifiable_ids'));
                }
            )
            ->all();

        Notification::send($selectedNotifiables, $this->notification);

        return response()->json([
            'message' => __('notifications::messages.sent'),
        ]);
    }
}
