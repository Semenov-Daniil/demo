<?php

namespace app\controllers;

use app\models\LoginForm;
use app\models\Passwords;
use app\models\Roles;
use app\models\Users;
use Yii;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;

class UserController extends \yii\web\Controller
{
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'only' => ['login', 'logout'],
                'rules' => [
                    [
                        'allow' => true,
                        'actions' => ['login'],
                        'roles' => ['?'],
                    ],
                    [
                        'actions' => ['logout'],
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
                'denyCallback' => function ($rule, $action) {
                    Yii::$app->user->isGuest ? $this->redirect(['user/login']) : $this->redirect(['/']);
                }
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'logout' => ['post'],
                ],
            ],
        ];
    }

    /**
     * Login user
     */
    public function actionLogin()
    {
        $this->layout = 'login';

        $model = new LoginForm();

        if (Yii::$app->request->isPost && $model->load(Yii::$app->request->post()) && $model->login()) {
            $this->goHome();
        }

        $model->password = '';
        return $this->render('login', [
            'model' => $model,
            'users' => [
                'expert' => Users::find()
                    ->select(['login', Passwords::tableName() . '.password'])
                    ->where(['roles_id' => Roles::getRoleId('expert')])
                    ->joinWith('passwords', false)
                    ->one(),
                'student' => Users::find()
                    ->select(['login', Passwords::tableName() . '.password'])
                    ->where(['roles_id' => Roles::getRoleId('student')])
                    ->joinWith('passwords', false)
                    ->one(),
            ]
        ]);
    }

    public function actionLogout()
    {
        if  (Yii::$app->request->isPost) {
            Yii::$app->user->logout();
        }
        return $this->goHome();
    }

    public function actionDeleteExpert()
    {
        if (Yii::$app->request->isAjax) {
            $user = new Users();
            $user->id = Yii::$app->request->post()['id'];
            if (Yii::$app->user->can('expert') && $user->deleteUser()) {
                Yii::$app->session->setFlash('success', "Эксперт успешно удален.");
            } else {
                Yii::$app->session->setFlash('error', "Не удалось удалить эксперта.");
            }
        }

        return $this->redirect(['site/settings']);
    }

    public function actionDeleteStudent()
    {
        if (Yii::$app->request->isAjax) {
            $user = new Users();
            $user->id = Yii::$app->request->post()['id'];
            if (Yii::$app->user->can('expert') && $user->deleteUser()) {
                Yii::$app->session->setFlash('success', "Студент успешно удален.");
            } else {
                Yii::$app->session->setFlash('error', "Не удалось удалить студента.");
            }
        }

        return $this->redirect(['site/students']);
    }
}
