<?php
return [
    'class' => yii\console\Application::class,
    'id' => 'yii2-console-application',
    'controllerMap' => [
        'migrate' => [
            'class' => paw\commands\MigrateController::class,
            'templateFile' => '@paw/db/views/migration-db.php',
            'migrationNamespaces' => [
                'paw\migrations\db',
            ],
        ],
        'rbac-migrate' => [
            'class' => paw\commands\MigrateController::class,
            'templateFile' => '@paw/db/views/migration-rbac.php',
            'migrationNamespaces' => [
                'paw\migrations\rbac',
            ],
        ],
    ],
];