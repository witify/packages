<?php

namespace Witify\Devops\Http\Controllers;

use Illuminate\Contracts\View\View;
use Witify\Devops\Actions\GetApplicationInfoAction;
use Witify\Devops\Actions\GetHealthSummaryAction;
use Witify\Devops\Actions\ResolveDeveloperToolsAction;

class DeveloperConsoleController
{
    public function __invoke(
        ResolveDeveloperToolsAction $resolveDeveloperToolsAction,
        GetApplicationInfoAction $getApplicationInfoAction,
        GetHealthSummaryAction $getHealthSummaryAction
    ): View {
        return view('devops::console', [
            'tools' => $resolveDeveloperToolsAction->handle(),
            'application' => $getApplicationInfoAction->handle(),
            'health' => $getHealthSummaryAction->handle(),
            'sentryTestEnabled' => SentryTestController::isAvailable(),
        ]);
    }
}
