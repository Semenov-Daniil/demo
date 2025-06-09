<?php

namespace backend\controllers;

use Yii;
use yii\web\Controller;

class BaseController extends Controller
{
    protected function addToastMessage(string $message, string $type): void
    {
        Yii::$app->session->addFlash('toastify', ['text' => $message, 'type' => $type]);
    }

    protected function renderAjaxIfRequested(string $view, array $params): string
    {
        return $this->request->isAjax ? $this->renderAjax($view, $params) : $this->render($view, $params);
    }
}