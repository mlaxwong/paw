<?php
return [
    'class' => paw\web\Application::class,
    'id' => 'yii2-web-application',
    'components' => [
        'urlManager' => [
            'enablePrettyUrl' => true,
            'showScriptName' => false,
            'enableStrictParsing' => false,
        ],
        'view' => [
            'class' => paw\web\View::class,
        ],
    ],
    // 'on beforeRequest' => function () {
    //     $app = Yii::$app;
    //     $pathInfo = $app->request->pathInfo;
    //     if (!empty($pathInfo) && substr($pathInfo, -1) !== '/') {
    //         $app->response->redirect('/' . rtrim($pathInfo) . '/', 301);
    //     }
    // },
];