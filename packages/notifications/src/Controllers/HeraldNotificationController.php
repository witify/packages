<?php

namespace Witify\Notifications\Controllers;

use Illuminate\Http\JsonResponse;
use Witify\Notifications\Actions\BuildHeraldNotificationDetailsAction;
use Witify\Notifications\Actions\BuildHeraldNotificationIndexAction;
use Witify\Support\Controller\Controller;

class HeraldNotificationController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'data' => (new BuildHeraldNotificationIndexAction)->handle(),
        ]);
    }

    public function show(string $notificationClass): JsonResponse
    {
        return response()->json([
            'data' => (new BuildHeraldNotificationDetailsAction($notificationClass))->handle(),
        ]);
    }
}
