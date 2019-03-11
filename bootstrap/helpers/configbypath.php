<?php
function configbypath($dir, $options = [])
{
    $options = \yii\helpers\ArrayHelper::merge([
        'env'       => ENV,
        'filename'  => 'config.php'
    ], $options);

    return configbuilder([
        $dir . '/' . $options['filename'],
        $dir . '/' . $options['env'] . '.' . $options['filename'],
    ]);
}