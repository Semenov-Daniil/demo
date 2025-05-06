<?php

$params = array_merge(
    require __DIR__ . '/../../common/config/params.php',
    require __DIR__ . '/../../common/config/params-local.php',
    require __DIR__ . '/params.php',
    require __DIR__ . '/params-local.php'
);

return [
    'id' => 'app-console',
    'basePath' => dirname(__DIR__),
    'bootstrap' => [
        'log',
        // 'queue',
        // 'redis',
    ],
    'controllerNamespace' => 'console\controllers',
    'aliases' => [
        '@bower' => '@vendor/bower-asset',
        '@npm'   => '@vendor/npm-asset',
    ],
    'controllerMap' => [
        'fixture' => [
            'class' => \yii\console\controllers\FixtureController::class,
            'namespace' => 'common\fixtures',
        ],
        'migrate' => [
            'class' => 'yii\console\controllers\MigrateController',
            'migrationPath' => [
                '@console/migrations',
                '@yii/rbac/migrations',
            ],
        ],
        // 'clear' => 'console\controllers\ClearController',
    ],
    'components' => [
        'log' => [
            'targets' => [
                [
                    'class' => \yii\log\FileTarget::class,
                    'levels' => ['error', 'warning'],
                ],
            ],
        ],
        // 'redis' => [
        //     'class' => 'yii\redis\Connection',
        //     'hostname' => 'localhost',
        //     'port' => 6379,
        //     'database' => 0,
        // ],
        // 'queue' => [
        //     'class' => 'yii\queue\redis\Queue',
        //     'redis' => 'redis',
        //     'channel' => 'queue',
        // ],
    ],
    'params' => $params,
];
