<?php

namespace app\controllers;

use Yii;
use yii\filters\AccessControl;
use yii\web\Controller;

class StudentController extends Controller
{
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'allow' => true,
                        'roles' => ['student'],
                    ]
                ],
                'denyCallback' => function ($rule, $action) {
                    Yii::$app->user->isGuest ? $this->redirect(['login']) : $this->redirect(['/']);
                }
            ],
        ];
    }

    /**
     * Displays homepage student.
     *
     * @return string
     */
    public function actionIndex()
    {
        return $this->render('index');
    }
}
