<?php

return [
    'title' => 'Developer Console',
    'subtitle' => 'Monitoring tools and state of the application.',
    'tools' => [
        'health' => [
            'label' => 'Laravel Health',
            'description' => 'Latest results of the health checks.',
        ],
        'horizon' => [
            'label' => 'Laravel Horizon',
            'description' => 'Queues, workers and failed jobs.',
        ],
        'pulse' => [
            'label' => 'Laravel Pulse',
            'description' => 'Performance and usage of the application.',
        ],
        'telescope' => [
            'label' => 'Laravel Telescope',
            'description' => 'Requests, queries, jobs and exceptions in detail.',
        ],
        'logs' => [
            'label' => 'Logs',
            'description' => 'Log files of the application.',
        ],
    ],
    'application' => [
        'title' => 'Application',
        'environment' => 'Environment',
        'version' => 'Version',
        'php' => 'PHP',
        'laravel' => 'Laravel',
        'debug' => 'Debug mode',
        'maintenance' => 'Maintenance mode',
        'configuration_cached' => 'Configuration cached',
        'routes_cached' => 'Routes cached',
        'github' => 'GitHub repository',
        'sentry' => 'Sentry project',
        'on' => 'on',
        'off' => 'off',
        'unknown' => 'unknown',
    ],
    'health' => [
        'title' => 'Health checks',
        'checked_at' => 'Last run :time',
        'no_results' => 'No results yet. The checks run from the scheduler with health:check.',
        'ok' => 'ok',
        'warning' => 'warning',
        'failed' => 'failed',
        'crashed' => 'crashed',
        'skipped' => 'skipped',
        'view' => 'View the results',
    ],
    'sentry' => [
        'title' => 'Sentry test',
        'description' => 'Send a test exception to Sentry to verify the integration.',
        'button' => 'Send a test exception',
        'sent' => 'Test exception sent. It appears in Sentry within a minute.',
    ],
];
