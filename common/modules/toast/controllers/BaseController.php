<?php

namespace common\modules\toast\controllers;

use Yii;
use yii\web\Controller;

/**
 * Default controller for the `toast` module
 */
class BaseController extends Controller
{
    public function actionMessages()
    {
        $messages = Yii::$app->session->getFlash('toastify', []);
        Yii::$app->session->close();
        return $this->asJson($messages);
    }
}
