<?php

namespace app\controllers;

use app\models\Users;
use Yii;

class UserController extends \yii\web\Controller
{
    public function actionIndex()
    {
        return $this->render('index');
    }

    /**
     * Login 
     */
    public function actionLogin()
    {
        $this->layout = 'login';

        if (!Yii::$app->user->isGuest) {
            return $this->goHome();
        }

        $login = Users::login();

        if ($login['status']) {
            return $this->goHome();
        } else {
            return $this->render('login', [
                'model' => $login['model'],
            ]);
        }
    }

    public function actionLogout()
    {
        Users::logout();
        return $this->goHome();
    }

    public function actionCreateExpert()
    {
        if (Yii::$app->user->isGuest) {
            return Yii::$app->response->redirect('/login');
        }

        $create = Users::createExpert();

        if ($create['status']) {
            return $this->goHome();
        } else {
            return $this->render('createExpert', [
                'model' => $create['model'],
                'dataProvider' => Users::getDataProvider(20),
            ]);
        }
    }
}
