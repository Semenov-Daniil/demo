<?php

namespace app\controllers;

use app\models\Users;
use Yii;
use yii\filters\AccessControl;

class UserController extends \yii\web\Controller
{
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'only' => ['login'],
                'rules' => [
                    [
                        'allow' => true,
                        'actions' => ['login'],
                        'roles' => ['?'],
                    ],
                ],
                'denyCallback' => function ($rule, $action) {
                    Yii::$app->user->isGuest ? $this->redirect(['user/login']) : $this->redirect(['/']);
                }
            ],
        ];
    }

    /**
     * Login user
     */
    public function actionLogin()
    {
        $this->layout = 'login';

        $login = Users::login();

        return $login['status'] ? $this->goHome() : $this->render('login', ['model' => $login['model']]);
    }

    public function actionLogout()
    {
        Users::logout();
        return $this->goHome();
    }

    /**
     * Deletes an existing Groups model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param int $id ID
     * @return \yii\web\Response
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionDelete($id)
    {
        if (Yii::$app->user->isGuest || Users::findOne(Yii::$app->user->id)->getTitleRoles() !== 'Admin') {
            return $this->goHome();
        }

        Users::deleteUser($id);

        return $this->redirect('/');
    }
}
