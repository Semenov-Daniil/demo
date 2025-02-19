<?php
return [
    'aliases' => [
        '@bower' => '@vendor/bower-asset',
        '@npm'   => '@vendor/npm-asset',
        '@events' => '@common/events',
        '@students' => '@common/students',
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
            // 'appendTimestamp' => true,
            // 'linkAssets' => true,
            'forceCopy' => true,
        ]
    ],
];
