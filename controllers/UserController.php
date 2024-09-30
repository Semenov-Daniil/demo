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
        if (!Yii::$app->user->isGuest) {
            return $this->goHome();
        }

        $model = Users::login();

        if ($model['status']) {
            return $this->goHome();
        } else {
            return $this->render('login', [
                'model' => $model['model'],
            ]);
        }
    }

    public function actionLogout()
    {
        Users::logout();
        return $this->goHome();
    }
}
