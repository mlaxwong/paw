<?php
return [
    'id' => 'yii2-application',
    'aliases' => [
        '@bower' => '@vendor/bower-asset',
        '@npm'   => '@vendor/npm-asset',
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
    ]
];