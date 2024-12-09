<?php

namespace app\modules\account\controllers;

use Yii;
use yii\filters\VerbFilter;
use yii\web\Controller;

class EventController extends Controller
{
    /**
     * Displays index page.
     *
     * @return string
     */
    public function actionIndex(): string
    {
        return $this->render('index');
    }
}
