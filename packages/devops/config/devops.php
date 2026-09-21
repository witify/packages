<?php

return [

    /*
     * Bearer token the portal sends to read the health endpoint.
     * The endpoint answers 404 while this token is empty.
     */
    'token' => env('PORTAL_HEALTH_TOKEN'),

    /*
     * Version of the application, reported to the portal.
     */
    'version' => env('APP_VERSION'),

    'route' => [
        'prefix' => 'api/devops',
        'middleware' => ['api'],
    ],

    /*
     * DSN of the Sentry project, used by the portal to link the application to its issues.
     */
    'sentry_dsn' => env('SENTRY_LARAVEL_DSN', env('SENTRY_DSN')),

    /*
     * Filesystem disk that receives the backups. Its bucket is reported to the portal,
     * together with the backup name of spatie/laravel-backup when it is installed.
     */
    'backup_disk' => 'backup',

];
