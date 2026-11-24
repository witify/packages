<?php

namespace Witify\Devops\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Witify\Devops\Actions\GetPortalHealthAction;
use Witify\Devops\Actions\StoreReleaseVersionAction;

class PortalHealthController
{
    public function __invoke(
        Request $request,
        StoreReleaseVersionAction $storeReleaseVersionAction,
        GetPortalHealthAction $getPortalHealthAction
    ): JsonResponse {
        $storeReleaseVersionAction->handle($request->header(StoreReleaseVersionAction::HEADER));

        return response()
            ->json($getPortalHealthAction->handle()->toArray())
            ->header('Cache-Control', 'no-store, private');
    }
}
