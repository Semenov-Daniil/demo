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
        return $this->asJson(Yii::$app->session->getFlash('toast-alert', []));
    }
}
