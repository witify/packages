<?php

return [
    'title' => 'Console développeur',
    'subtitle' => 'Page réservée aux développeurs pour surveiller et tester l\'application.',
    'tools' => [
        'health' => [
            'label' => 'Laravel Health',
            'description' => 'Vérifier la santé de l\'application Laravel.',
        ],
        'horizon' => [
            'label' => 'Laravel Horizon',
            'description' => 'Surveiller les files d\'attente de l\'application.',
        ],
        'pulse' => [
            'label' => 'Laravel Pulse',
            'description' => 'Performance et utilisation de l\'application.',
        ],
        'telescope' => [
            'label' => 'Laravel Telescope',
            'description' => 'Inspecter les requêtes, requêtes SQL, tâches et exceptions.',
        ],
        'logs' => [
            'label' => 'Journaux',
            'description' => 'Consulter les journaux de l\'application.',
        ],
    ],
    'application' => [
        'title' => 'Application',
        'environment' => 'Environnement',
        'version' => 'Version',
        'php' => 'PHP',
        'laravel' => 'Laravel',
        'debug' => 'Mode débogage',
        'maintenance' => 'Mode maintenance',
        'configuration_cached' => 'Configuration en cache',
        'routes_cached' => 'Routes en cache',
        'github' => 'Dépôt GitHub',
        'sentry' => 'Projet Sentry',
        'on' => 'activé',
        'off' => 'désactivé',
        'unknown' => 'inconnu',
    ],
    'health' => [
        'title' => 'Vérifications de santé',
        'checked_at' => 'Dernière exécution :time',
        'no_results' => 'Aucun résultat pour l\'instant. Les vérifications tournent par le planificateur avec health:check.',
        'ok' => 'ok',
        'warning' => 'avertissement',
        'failed' => 'échec',
        'crashed' => 'plantage',
        'skipped' => 'ignoré',
        'view' => 'Voir les résultats',
    ],
    'sentry' => [
        'title' => 'Test Sentry',
        'description' => 'Envoie une exception de test à Sentry pour vérifier que l\'intégration fonctionne.',
        'button' => 'Exception backend',
        'sent' => 'Backend envoyé',
        'event_id' => 'ID de l\'événement : :id',
    ],
];
