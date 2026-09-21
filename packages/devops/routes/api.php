<?php

use Illuminate\Support\Facades\Route;
use Witify\Devops\Http\Controllers\PortalHealthController;
use Witify\Devops\Http\Middleware\VerifyPortalHealthToken;

$middleware = array_merge((array) config('devops.route.middleware'), [VerifyPortalHealthToken::class]);

Route::middleware($middleware)
    ->prefix((string) config('devops.route.prefix'))
    ->name('api.devops.')
    ->group(function (): void {
        Route::get('health', PortalHealthController::class)->name('health.show');
    });
