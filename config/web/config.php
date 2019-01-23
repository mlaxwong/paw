<?php
return [
    'class' => yii\web\Application::class,
    'id' => 'yii2-web-application',
    'components' => [
        'urlManager' => [
            'enablePrettyUrl' => true,
            'showScriptName' => false,
            'enableStrictParsing' => false,
        ],
    ],
];