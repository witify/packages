<?php

return [
    'title' => 'Developer Console',
    'subtitle' => 'This is a page for developers to monitor and test the application.',
    'tools' => [
        'health' => [
            'label' => 'Laravel Health',
            'description' => 'Check the health of the Laravel application.',
        ],
        'horizon' => [
            'label' => 'Laravel Horizon',
            'description' => 'Monitor the queues of the application.',
        ],
        'pulse' => [
            'label' => 'Laravel Pulse',
            'description' => 'Insights into the application\'s performance and usage.',
        ],
        'telescope' => [
            'label' => 'Laravel Telescope',
            'description' => 'Inspect the requests, queries, jobs and exceptions.',
        ],
        'logs' => [
            'label' => 'Logs',
            'description' => 'View the logs of the application.',
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
        'title' => 'Health Checks',
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
        'title' => 'Sentry Test',
        'description' => 'Send a test exception to Sentry to verify the integration is working.',
        'button' => 'Backend exception',
        'sent' => 'Backend sent',
        'event_id' => 'Event ID: :id',
    ],
    'echo' => [
        'title' => 'Echo Test',
        'description' => 'Broadcast a test event on your private user channel and watch it arrive over the WebSocket.',
        'socket' => 'Socket: :state',
        'connected' => 'connected',
        'disconnected' => 'disconnected',
        'connecting' => 'connecting',
        'button' => 'Broadcast',
        'sent' => 'Sent on :channel',
        'received' => 'Received',
        'error' => 'The broadcast failed (:status).',
    ],
];
