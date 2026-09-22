<?php

return [
    'title' => 'Console développeur',
    'subtitle' => 'Outils de surveillance et état de l\'application.',
    'tools' => [
        'health' => [
            'label' => 'Laravel Health',
            'description' => 'Derniers résultats des vérifications de santé.',
        ],
        'horizon' => [
            'label' => 'Laravel Horizon',
            'description' => 'Files d\'attente, workers et tâches en échec.',
        ],
        'pulse' => [
            'label' => 'Laravel Pulse',
            'description' => 'Performance et utilisation de l\'application.',
        ],
        'telescope' => [
            'label' => 'Laravel Telescope',
            'description' => 'Requêtes, requêtes SQL, tâches et exceptions en détail.',
        ],
        'logs' => [
            'label' => 'Journaux',
            'description' => 'Fichiers de journal de l\'application.',
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
        'description' => 'Envoie une exception de test à Sentry pour vérifier l\'intégration.',
        'button' => 'Envoyer une exception de test',
        'sent' => 'Exception de test envoyée. Elle apparaît dans Sentry en moins d\'une minute.',
    ],
];
