<?php

namespace common\modules\flash\controllers;

use Yii;
use yii\web\Controller;

/**
 * Default controller for the `flash` module
 */
class BaseController extends Controller
{
    public function actionGetMessages()
    {
        $messages = Yii::$app->session->getFlash('toastify', []);
        Yii::$app->session->close();
        return $this->asJson($messages);
    }
}
