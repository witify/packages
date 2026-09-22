<?php

use Illuminate\Support\Facades\Route;
use Spatie\Health\Http\Controllers\HealthCheckResultsController;
use Witify\Devops\Http\Controllers\DeveloperConsoleController;
use Witify\Devops\Http\Controllers\SentryTestController;

$consolePath = config('devops.console.path');
$healthPath = config('devops.console.health_path');

Route::middleware((array) config('devops.console.middleware'))->group(function () use ($consolePath, $healthPath): void {
    if (is_string($consolePath) && $consolePath !== '') {
        Route::get($consolePath, DeveloperConsoleController::class)->name('devops.console');
        Route::post($consolePath . '/sentry-test', SentryTestController::class)->name('devops.console.sentry_test');
    }

    if (is_string($healthPath) && $healthPath !== '') {
        Route::get($healthPath, HealthCheckResultsController::class)->name('devops.health');
    }
});
