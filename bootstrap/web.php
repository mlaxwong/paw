<?php
$app = require __DIR__ . '/bootstrap.php';

define('APP_TYPE', basename(__FILE__, '.php'));

return \yii\helpers\ArrayHelper::merge($app, configbuilder([
    dirname(__DIR__) . '/config/' . APP_TYPE . '/config.php',
    dirname(__DIR__) . '/config/' . APP_TYPE . '/' . ENV . '.config.php',
]));