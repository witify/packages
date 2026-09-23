<?php

namespace Witify\Devops\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Witify\Devops\Actions\GetApplicationInfoAction;
use Witify\Devops\Actions\GetEchoClientConfigAction;
use Witify\Devops\Actions\GetHealthSummaryAction;
use Witify\Devops\Actions\ResolveDeveloperToolsAction;

class DeveloperConsoleController
{
    public function __invoke(
        Request $request,
        ResolveDeveloperToolsAction $resolveDeveloperToolsAction,
        GetApplicationInfoAction $getApplicationInfoAction,
        GetHealthSummaryAction $getHealthSummaryAction,
        GetEchoClientConfigAction $getEchoClientConfigAction
    ): View {
        $user = $request->user();

        return view('devops::console', [
            'tools' => $resolveDeveloperToolsAction->handle(),
            'application' => $getApplicationInfoAction->handle(),
            'health' => $getHealthSummaryAction->handle(),
            'sentryTestEnabled' => SentryTestController::isAvailable(),
            'echo' => $user === null ? null : $getEchoClientConfigAction->handle($user->getAuthIdentifier()),
        ]);
    }
}
