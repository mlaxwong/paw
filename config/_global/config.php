<?php
return [
    'id' => 'yii2-application',
    'timeZone' => 'Asia/Kuala_Lumpur',
    'aliases' => [
        '@bower' => '@vendor/bower-asset',
        '@npm' => '@vendor/npm-asset',
        '@paw' => dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR . 'src',
    ],
    'components' => [
        'db' => [
            'class' => yii\db\Connection::class,
            'dsn' => env('DB_DSN', 'mysql:host=localhost;port=3306;dbname=database_v1'),
            'username' => env('DB_USERNAME'),
            'password' => env('DB_PASSWORD'),
            'tablePrefix' => env('DB_TABLE_PREFIX', 'prefix_'),
            'charset' => 'utf8',
            // 'enableSchemaCache' => YII_ENV_PROD,
        ],
        'authManager' => [
            'class' => yii\rbac\DbManager::class,
        ],
        'resource' => [
            'class' => paw\services\Resource::class,
            'namespaces' => [
                'paw\\resources',
            ],
        ],
        'cache' => [
            'class' => yii\caching\FileCache::class,
        ],
        'mailer' => [
            'class' => yii\swiftmailer\Mailer::class,
        ],
        'mailqueue' => [
            'class' => nterms\mailqueue\MailQueue::class,
            'table' => '{{%mail_queue}}',
            'mailsPerRound' => 10,
            'maxAttempts' => 3,
        ],
    ],
];
