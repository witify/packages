<?php

namespace Witify\Devops\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Witify\Devops\Actions\GetEchoClientConfigAction;
use Witify\Devops\Events\EchoTestEvent;

class EchoTestController
{
    public const DEFAULT_MESSAGE = 'Hello from Echo!';

    public function __invoke(Request $request, GetEchoClientConfigAction $getEchoClientConfigAction): JsonResponse
    {
        $user = $request->user();

        abort_unless($user !== null, 403);

        $config = $getEchoClientConfigAction->handle($user->getAuthIdentifier());

        abort_unless($config !== null, 404);

        /** @var array{message?: string|null} $validated */
        $validated = $request->validate([
            'message' => ['nullable', 'string', 'max:255'],
        ]);

        $event = new EchoTestEvent($config->channel, $validated['message'] ?? self::DEFAULT_MESSAGE);

        event($event);

        return new JsonResponse([
            'channel' => $event->broadcastOn()->name,
            'message' => $event->message,
            'sent_at' => $event->sentAt,
        ]);
    }
}
