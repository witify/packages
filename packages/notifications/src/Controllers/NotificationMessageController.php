<?php

namespace Witify\Notifications\Controllers;

use Illuminate\Http\JsonResponse;
use Witify\Notifications\Actions\DeleteNotificationMessagesAction;
use Witify\Notifications\Actions\UpdateNotificationMessagesAction;
use Witify\Notifications\Requests\DeleteNotificationMessagesRequest;
use Witify\Notifications\Requests\UpdateNotificationMessagesRequest;
use Witify\Support\Controller\Controller;

class NotificationMessageController extends Controller
{
    public function updateBatch(UpdateNotificationMessagesRequest $request): JsonResponse
    {
        (new UpdateNotificationMessagesAction($request->notificationMessages()))->handle();

        return response()->json([
            'message' => __('notifications::messages.updated'),
        ]);
    }

    public function destroy(DeleteNotificationMessagesRequest $request): JsonResponse
    {
        (new DeleteNotificationMessagesAction(
            notificationClass: $request->notificationClass(),
            channel: $request->channel(),
        ))->handle();

        return response()->json([
            'message' => __('notifications::messages.deleted'),
        ]);
    }
}
