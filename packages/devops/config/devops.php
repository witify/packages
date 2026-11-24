<?php

return [

    /*
     * Bearer token the portal sends to read the health endpoint.
     * The endpoint answers 404 while this token is empty.
     */
    'token' => env('PORTAL_HEALTH_TOKEN'),

    'route' => [
        'prefix' => 'api/devops',
        'middleware' => ['api'],
    ],

    /*
     * DSN of the Sentry project, used by the portal to link the application to its issues.
     */
    'sentry_dsn' => env('SENTRY_LARAVEL_DSN', env('SENTRY_DSN')),

    /*
     * DSNs of the other Sentry projects of the application, such as the one of the
     * frontend. Their project ids are reported to the portal after the one of `sentry_dsn`.
     */
    'sentry_additional_dsns' => array_values(array_filter([env('SENTRY_FRONTEND_DSN') ?: env('VITE_SENTRY_DSN')])),

    /*
     * GitHub repository of the application, as `owner/repo`. It wins over the `origin`
     * remote of `.git/config`, which a deployment without a checkout (Laravel Cloud) lacks.
     */
    'github_repository' => env('DEVOPS_GITHUB_REPOSITORY'),

    /*
     * Filesystem disk that receives the backups. Its bucket is reported to the portal,
     * together with the backup name of spatie/laravel-backup when it is installed.
     */
    'backup_disk' => 'backup',

    /*
     * Developer console: a page listing the monitoring tools installed in the application.
     * `path` serves the console and `health_path` the results page of laravel-health;
     * null disables either page. Add the middleware that restricts them to your
     * administrators. `links` overrides or adds tool URLs, e.g. ['logs' => '/my-logs'].
     *
     * `echo_channel` enables the Echo test: the private channel of the authenticated
     * user, with `{id}` replaced by the user id, e.g. 'user.{id}' or 'App.Models.User.{id}'.
     * The test stays hidden while it is null or while the default broadcaster is
     * neither Pusher nor Reverb.
     */
    'console' => [
        'path' => 'devops',
        'health_path' => 'status',
        'middleware' => ['web', 'auth'],
        'links' => [],
        'echo_channel' => null,
    ],

];
