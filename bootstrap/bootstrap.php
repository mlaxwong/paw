<?php
// requirement
require __DIR__ . '/requirement.php';

// composer auto load
require PATH_VENDOR . '/autoload.php';

// bootstrap helpers
require __DIR__ . '/helpers/env.php';
require __DIR__ . '/helpers/configbuilder.php';

// load env
// (new Dotenv\Dotenv(PATH_CONFIG))->load();

// constants
defined('ENV') or define('ENV', env('ENV') ?: 'prod');
defined('YII_ENV') or define('YII_ENV', env('ENV') ?: 'prod');
defined('YII_DEBUG') or define('YII_DEBUG', env('ENV') == 'dev' ?: false);

// Yii2
require PATH_VENDOR . '/yiisoft/yii2/Yii.php';

// config
return configbuilder([
    dirname(__DIR__) . '/config/_global/config.php',
    dirname(__DIR__) . '/config/_global/' . ENV . '.config.php',
]);