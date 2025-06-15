<?php

namespace backend\controllers;

use common\traits\SseTrait;
use Yii;
use yii\web\Controller;

class BaseController extends Controller
{
    protected function renderAjaxIfRequested(string $view, array $params): string
    {
        return $this->request->isAjax ? $this->renderAjax($view, $params) : $this->render($view, $params);
    }
}