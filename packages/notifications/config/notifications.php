<?php

return [

    /*
     * User model of the application. It implements Witify\Notifications\Herald\HeraldUser
     * and carries the `notification_settings` (json) and `timezone` columns.
     * Null falls back to the model of the default auth provider.
     */
    'user_model' => null,

    /*
     * Timezone of the recipients that do not carry their own. The application
     * can override it with Herald::resolveTimezoneUsing().
     */
    'timezone' => env('HERALD_TIMEZONE', 'UTC'),

    /*
     * Locales the notifications are written in, e.g. ['fr', 'en'].
     * Null reads `app.locales` (codes as keys), then `app.locale`.
     */
    'locales' => null,

    /*
     * Recipient of the DeveloperNotifiable, for the technical notifications.
     */
    'developer' => [
        'first_name' => env('DEVELOPER_FIRST_NAME'),
        'last_name' => env('DEVELOPER_LAST_NAME'),
        'email' => env('DEVELOPER_EMAIL'),
        'locale' => 'fr',
    ],

    /*
     * The API routes of the inbox (`middleware`) and of the notification
     * previews and messages editor (`admin_middleware`). Add the middleware
     * that restricts the editor to your administrators.
     */
    'routes' => [
        'enabled' => true,
        'prefix' => 'api',
        'middleware' => ['web', 'auth'],
        'admin_middleware' => ['web', 'auth'],
    ],

];
