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
        [
            'class' => 'common\components\FileComponent',
            'directories' => [
                '@common/students',
                '@common/events'
            ],
        ],
        'queue',
        'redis'
    ],
    'modules' => [
        'flash' => [
            'class' => 'common\modules\flash\Module',
            'defaultRoute' => 'base'
        ],
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
        'user' => [
            'identityClass' => 'common\models\Users',
            'enableAutoLogin' => false,
            'identityCookie' => ['name' => '_identity-backend', 'httpOnly' => true],
            'loginUrl' => ['login'],
        ],
        'session' => [
            // this is the name of the session cookie used for login on the backend
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

                '/flash' => '/flash', 
                '/flash/<action>' => '/flash/base/<action>',

                '/file/download/<filePath:.*>' => '/file/download',
                // '/<action>' => '/main/dispatch', 
            ],
        ],

        'redis' => [
            'class' => 'yii\redis\Connection',
            'hostname' => 'localhost',
            'port' => 6379,
            'database' => 0,
        ],
        'queue' => [
            'class' => 'yii\queue\redis\Queue',
            'redis' => 'redis', // ID компонента Redis
            'channel' => 'queue', // Название канала
        ],
        
    ],
    'params' => $params,
];
