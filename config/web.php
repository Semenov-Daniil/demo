<?php

$params = require __DIR__ . '/params.php';
$db = require __DIR__ . '/db.php';

$config = [
    'id' => 'demo',
    'name' => 'demo',
    'basePath' => dirname(__DIR__),
    'bootstrap' => [
        'log',
        [
            'class' => 'app\components\FileComponent',
            'directories' => [
                '@users',
                '@competencies'
            ],
        ]
    ],
    'language' => 'ru-Ru',
    'aliases' => [
        '@bower' => '@vendor/bower-asset',
        '@npm'   => '@vendor/npm-asset',
        '@users' => '@app/users',
        '@competencies' => '@app/competencies',
    ],
    'components' => [
        'request' => [
            // !!! insert a secret key in the following (if it is empty) - this is required by cookie validation
            'cookieValidationKey' => 'mbpvOpHTC0G9lSYi96SJZNUs4AL-Ayy_',
            'baseUrl' => '',
            'parsers' => [
                'application/json' => 'yii\web\JsonParser',
            ],
            'csrfCookie' => [
                'httpOnly' => true,
                'expire' => 0,
            ],
        ],
        'cache' => [
            'class' => 'yii\caching\FileCache',
        ],
        'user' => [
            'identityClass' => 'app\models\Users',
            'enableAutoLogin' => false,
        ],
        'errorHandler' => [
            'errorAction' => 'site/error',
        ],
        'mailer' => [
            'class' => \yii\symfonymailer\Mailer::class,
            'viewPath' => '@app/mail',
            // send all mails to a file by default.
            'useFileTransport' => true,
        ],
        'log' => [
            'traceLevel' => YII_DEBUG ? 3 : 0,
            'targets' => [
                [
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['error', 'warning'],
                ],
            ],
        ],
        'db' => $db,
        'defaultRoute' => 'login',
        'urlManager' => [
            'enablePrettyUrl' => true,
            'enableStrictParsing' => true,
            'showScriptName' => false,
            'rules' => [
                '<action:(login|logout)>' => 'site/<action>',
                'GET download/<competence>/<filename>' => 'site/download',

                [
                    'class' => 'app\components\RoleBasedUrlRule', 
                    'pattern' => '/<action:.*>',
                    'verb' => ['GET', 'POST', 'PATH'],
                    'route' => 'expert/<action>',
                    'role' => 'expert'
                ],
                [
                    'class' => 'app\components\RoleBasedUrlRule', 
                    'pattern' => '/<action>/<id>',
                    'verb' => ['DELETE'],
                    'route' => 'expert/delete-<action>',
                    'role' => 'expert'
                ],
                [
                    'class' => 'app\components\RoleBasedUrlRule', 
                    'pattern' => '/<action:.*>',
                    'route' => 'student/<action>',
                    'role' => 'student'
                ],

                '/' => 'site/index',
                '<action>' => 'site/<action>',
            ],
        ],
        'authManager' => [
            'class' => 'yii\rbac\DbManager',
            'defaultRoles' => ['student', 'expert'],
        ],
        'session' => [
            'class' => 'yii\web\Session',
            'cookieParams' => [
                'httpOnly' => true,
                'lifetime' => 0,
            ],
            'timeout' => 1440,
            'useCookies' => true,
        ],
    ],
    'params' => $params,
];

if (YII_ENV_DEV) {
    // configuration adjustments for 'dev' environment
    $config['bootstrap'][] = 'debug';
    $config['modules']['debug'] = [
        'class' => 'yii\debug\Module',
        // uncomment the following to add your IP if you are not connecting from localhost.
        'allowedIPs' => ['*'],
    ];

    $config['bootstrap'][] = 'gii';
    $config['modules']['gii'] = [
        'class' => 'yii\gii\Module',
        // uncomment the following to add your IP if you are not connecting from localhost.
        'allowedIPs' => ['*'],
    ];
}

return $config;
