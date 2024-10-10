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
                'only' => [],
                'rules' => [
                    [
                        'allow' => true,
                        'roles' => ['student'],
                    ],
                ],
                'denyCallback' => function ($rule, $action) {
                    $this->redirect(['user/login']);
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
