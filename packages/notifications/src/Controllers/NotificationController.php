<?php

namespace Witify\Notifications\Controllers;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Witify\Notifications\Models\Notification;
use Witify\Support\Controller\Controller;

class NotificationController extends Controller
{
    private const PER_PAGE = 20;

    /**
     * @return LengthAwarePaginator<int, Notification>
     */
    public function index(Request $request): LengthAwarePaginator
    {
        return Notification::query()
            ->index($request)
            ->paginate($request->integer('per_page', self::PER_PAGE));
    }

    public function update(Notification $notification, Request $request): JsonResponse
    {
        $this->authorize('update', $notification);

        $notification->markAsRead();

        return response()->json([
            'message' => __('notifications::messages.marked_as_read'),
            'notification' => $notification,
        ]);
    }
}
