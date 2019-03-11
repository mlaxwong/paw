<?php
$app = require __DIR__ . '/bootstrap.php';

define('APP_TYPE', basename(__FILE__, '.php'));

return \yii\helpers\ArrayHelper::merge($app, configbypath(dirname(__DIR__) . '/config/' . APP_TYPE));