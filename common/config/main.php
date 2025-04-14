<?php
return [
    'aliases' => [
        '@bower' => '@vendor/bower-asset',
        '@npm'   => '@vendor/npm-asset',
        '@root' => dirname(dirname(__DIR__)),
        '@events' => '@root/events',
        '@students' => '@root/students',
        '@templates' => '@common/templates',
        '@bash' => '@root/bash',
        '@logs' => '@root/logs',
    ],
    'language' => 'ru-RU',
    'vendorPath' => dirname(dirname(__DIR__)) . '/vendor',
    'components' => [
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
];
