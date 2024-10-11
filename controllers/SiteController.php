<?php

namespace app\controllers;

use app\models\Users;
use Yii;
use yii\filters\AccessControl;
use yii\web\Controller;

class SiteController extends Controller
{
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'only' => [],
                'rules' => [
                    [
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                    [
                        'allow' => true,
                        'actions' => ['settings', 'students', 'modules', 'files', 'competitors'],
                        'roles' => ['expert'],
                    ],  
                ],
                'denyCallback' => function ($rule, $action) {
                    Yii::$app->user->isGuest ? $this->redirect(['user/login']) : $this->redirect(['/']);
                }
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function actions()
    {
        return [
            'error' => [
                'class' => 'yii\web\ErrorAction',
            ],
            'captcha' => [
                'class' => 'yii\captcha\CaptchaAction',
                'fixedVerifyCode' => YII_ENV_TEST ? 'testme' : null,
            ],
        ];
    }

    /**
     * Displays homepage.
     *
     * @return string
     */
    public function actionIndex()
    {
        return Yii::$app->user->can('student') ? $this->redirect(['student']) : $this->redirect(['settings']);
    }

    /**
     * Displays settings page.
     *
     * @return string
     */
    public function actionSettings()
    {
        $addExpert = Users::addExpert();

        return $this->render('settings', [
            'user' => $addExpert['user'],
            'testing' => $addExpert['testing'],
            'dataProvider' => Users::getDataProvider(20),
        ]);
    }

    /**
     * Displays student page.
     *
     * @return string
     */
    public function actionStudents()
    {
        return $this->render('students');
    }

    /**
     * Displays files page.
     *
     * @return string
     */
    public function actionFiles()
    {
        return $this->render('files');
    }

    /**
     * Displays modules page.
     *
     * @return string
     */
    public function actionModules()
    {
        return $this->render('modules');
    }

    /**
     * Displays competitors page.
     *
     * @return string
     */
    public function actionCompetitors()
    {
        return $this->render('competitors');
    }
}
