<?php
return [
    'id' => 'yii2-application',
    'aliases' => [
        '@bower' => '@vendor/bower-asset',
        '@npm'   => '@vendor/npm-asset',
        '@paw'   => dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR . 'src',
    ],
    'components' => [
        'db' => [
            'class' => 'yii\db\Connection',
            'dsn' => env('DB_DSN', 'mysql:host=localhost;port=3306;dbname=database_v1'),
            'username' => env('DB_USERNAME'),
            'password' => env('DB_PASSWORD'),
            'tablePrefix' => env('DB_TABLE_PREFIX', 'prefix_'),
            'charset' => 'utf8',
            // 'enableSchemaCache' => YII_ENV_PROD,
        ],
        'user' => [
            'class' => paw\services\User::class,
            'identityClass' => paw\models\User::class,
        ],
        'authManager' => [
            'class' => yii\rbac\DbManager::class,
        ],
        'resource' => [
            'class' => paw\services\Resource::class,
            'namespaces' => [
                'paw\\resources'
            ],
        ]
    ]
];