<?php

namespace Witify\Devops\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Witify\Devops\Actions\GetPortalHealthAction;

class PortalHealthController
{
    public function __invoke(GetPortalHealthAction $getPortalHealthAction): JsonResponse
    {
        return response()
            ->json($getPortalHealthAction->handle()->toArray())
            ->header('Cache-Control', 'no-store, private');
    }
}
