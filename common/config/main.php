<?php
return [
    'aliases' => [
        '@bower' => '@vendor/bower-asset',
        '@npm'   => '@vendor/npm-asset',
        '@events' => '@common/events',
        '@students' => '@common/students',
        '@templates' => '@common/templates',
    ],
    'language' => 'ru-RU',
    'vendorPath' => dirname(dirname(__DIR__)) . '/vendor',
    'modules' => [
        'debug' => [
            'class' => 'yii\debug\Module',
            'allowedIPs' => ['*'],
        ],
        'gii' => [
            'class' => 'yii\gii\Module',
            'allowedIPs' => ['*'],
        ],
    ],
    'components' => [
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
        'cache' => [
            'class' => \yii\caching\FileCache::class,
        ],
        'view' => [
            'theme' => [
                'pathMap' => [
                    '@frontend/views' => '@common/views',
                    '@backend/views' => '@common/views',
                ],
            ],
        ],
        'session' => [
            'class' => 'yii\web\Session',
            'cookieParams' => [
                'lifetime' => 0,
            ],
        ],
        'authManager' => [
            'class' => 'yii\rbac\DbManager',
            'defaultRoles' => ['student', 'expert'],
        ],
        'dbComponent' => [
            'class' => 'common\components\DbComponent'
        ],
        'fileComponent' => [
            'class' => 'common\components\FileComponent'
        ],
        'assetManager' => [
            'forceCopy' => true,
            'bundles' => [
                'yii\bootstrap5\BootstrapAsset' => [
                    'css' => []
                ],
                'yii\bootstrap5\BootstrapPluginAsset' => [
                    'js'=>[]
                ],
            ],
        ]
    ],
];
