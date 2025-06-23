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
    ],
    'language' => 'ru-RU',
    'vendorPath' => dirname(dirname(__DIR__)) . '/vendor',
    'bootstrap' => [
        [
            'class' => 'common\components\FileComponent',
            'directories' => [
                '@students',
                '@events',
            ],
        ],
        'queue',
        'redis',
        'redisSubscriber',
        'redisNotifications',
    ],
    'components' => [
        'cache' => [
            // 'class' => \yii\caching\FileCache::class,
            'class' => 'yii\redis\Cache',
        ],
        'session' => [
            'class' => 'yii\redis\Session',
            'redis' => 'redis', // указывает на компонент выше
            'keyPrefix' => 'session:',
            'timeout' => 3600,
        ],
        'view' => [
            'theme' => [
                'pathMap' => [
                    '@frontend/views' => '@common/views',
                    '@backend/views' => '@common/views',
                ],
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
        'commandComponent' => [
            'class' => 'common\components\CommandComponent'
        ],
        'toast' => [
            'class' => 'common\components\ToastComponent'
        ],
        'sse' => [
            'class' => 'common\components\SseComponent'
        ],
        'assetManager' => [
            // 'forceCopy' => true,
            'linkAssets' => true,
            'appendTimestamp' => true,
            'bundles' => [
                'yii\bootstrap5\BootstrapAsset' => [
                    'css' => []
                ],
                'yii\bootstrap5\BootstrapPluginAsset' => [
                    'js'=>[]
                ],
            ],
        ],
        'redis' => [
            'class' => 'yii\redis\Connection',
            'hostname' => 'localhost',
            'port' => 6379,
            'database' => 0,
        ],
        'redisNotifications' => [
            'class' => \Gevman\Yii2RedisSubscriber\Connection::class,
            'hostname' => 'localhost',
            'port' => 6379,
            'database' => 0,
        ],
        'redisSubscriber' => [
            'class' => \Gevman\Yii2RedisSubscriber\Connection::class,
            'hostname' => 'localhost',
            'port' => 6379,
            'database' => 0,
        ],
        'queue' => [
            'class' => \yii\queue\redis\Queue::class,
            'redis' => 'redis',
            'channel' => 'queue',
            'as log' => \yii\queue\LogBehavior::class,
        ]
    ],
    'modules' => [
        'debug' => [
            'class' => 'yii\debug\Module',
            'allowedIPs' => ['*'],
            'panels' => [
                'queue' => \yii\queue\debug\Panel::class
            ]
        ],
        'gii' => [
            'class' => 'yii\gii\Module',
            'allowedIPs' => ['*'],
        ],
    ],
];
