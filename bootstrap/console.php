<?php
$app = require __DIR__ . '/bootstrap.php';

define('APP_TYPE', basename(__FILE__, '.php'));

return \yii\helpers\ArrayHelper::merge($app, configbuilder([
    PATH_CONFIG . '/' . APP_TYPE . '/config.php',
    PATH_CONFIG . '/' . APP_TYPE . '/' . ENV . '.config.php',
]));