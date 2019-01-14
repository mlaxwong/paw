<?php
function configbuilder($configFiles)
{
    $configs = [];
    foreach ($configFiles as $configFile) 
    {
        if (file_exists($configFile)) 
        {
            $configs[] = require $configFile;
        }
    }
    if (!$configs) return [];
    return count($configs) > 1 ? call_user_func_array([\yii\helpers\ArrayHelper::class, 'merge'], $configs) : $configs[0];
}