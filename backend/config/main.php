<?php
$params = array_merge(
    require __DIR__ . '/../../common/config/params.php',
    require __DIR__ . '/../../common/config/params-local.php',
    require __DIR__ . '/params.php',
    require __DIR__ . '/params-local.php'
);

return [
    'id' => 'app-backend',
    'basePath' => dirname(__DIR__),
    'controllerNamespace' => 'backend\controllers',
    'bootstrap' => [
        'log',
    ],
    'defaultRoute' => 'expert/experts',
    'components' => [
        'request' => [
            'csrfParam' => '_csrf-backend',
            'baseUrl' => '/expert',
            'parsers' => [
                'application/json' => 'yii\web\JsonParser',
                'multipart/form-data' => 'yii\web\MultipartFormDataParser',
            ],
        ],
        'response' => [
            'class' => 'yii\web\Response',
            'on beforeSend' => function ($event) {
                $response = $event->sender;
                if ($response->statusCode == 500) {
                    Yii::$app->session->addFlash('toastify', [
                        'text' => 'Произошла внутренняя ошибка сервера.',
                        'type' => 'error'
                    ]);
                }
            },
        ],
        'user' => [
            'identityClass' => 'common\models\Users',
            'enableAutoLogin' => false,
            'identityCookie' => ['name' => '_identity-backend', 'httpOnly' => true],
            'loginUrl' => ['login'],
        ],
        'session' => [
            'name' => 'advanced-backend',
            'cookieParams' => [
                'lifetime' => 0,
            ],
        ],
        'log' => [
            'traceLevel' => YII_DEBUG ? 3 : 0,
            'targets' => [
                [
                    'class' => \yii\log\FileTarget::class,
                    'levels' => ['error', 'warning'],
                ],
            ],
        ],
        'errorHandler' => [
            'errorAction' => 'main/error',
        ],
        
        'urlManager' => [
            'enablePrettyUrl' => true,
            'showScriptName' => false,
            'enableStrictParsing' => false,
            'rules' => [
                '/<action:(login|logout)>' => '/main/<action>',

                '/toast/<action>' => '/toast/base/<action>',

                '/file/download/<filePath:.*>' => '/file/download',
            ],
        ],
    ],
    'modules' => [
        'toast' => [
            'class' => 'common\modules\toast\Module',
            'defaultRoute' => 'base'
        ],
    ],
    'params' => $params,
];
