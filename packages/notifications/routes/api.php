<?php

use Illuminate\Support\Facades\Route;
use Witify\Notifications\Controllers\HeraldNotificationController;
use Witify\Notifications\Controllers\NotificationController;
use Witify\Notifications\Controllers\NotificationMessageController;

$prefix = (string) config('notifications.routes.prefix', 'api');

Route::middleware((array) config('notifications.routes.middleware'))->prefix($prefix)->as('api.')->group(function (): void {
    Route::get('notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::patch('notifications/{notification}', [NotificationController::class, 'update'])->name('notifications.update');
});

Route::middleware((array) config('notifications.routes.admin_middleware'))->prefix($prefix)->as('api.')->group(function (): void {
    Route::get('herald-notifications', [HeraldNotificationController::class, 'index'])->name('herald_notifications.index');
    Route::post('herald-notifications/{notification_class}', [HeraldNotificationController::class, 'show'])->name('herald_notifications.show');

    Route::patch('notification-messages/batch', [NotificationMessageController::class, 'updateBatch'])->name('notification_messages.update.batch');
    Route::delete('notification-messages', [NotificationMessageController::class, 'destroy'])->name('notification_messages.destroy');
});
