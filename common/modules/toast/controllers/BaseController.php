<?php

namespace common\modules\toast\controllers;

use common\traits\SseTrait;
use Yii;
use yii\web\Controller;
use yii\web\Response;

/**
 * Default controller for the `toast` module
 */
class BaseController extends Controller
{
    use SseTrait;

    public $enableCsrfValidation = false;

    public function behaviors()
    {
        return [
            'access' => [
                'class' => \yii\filters\AccessControl::class,
                'rules' => [
                    [
                        'actions' => ['notifications', 'messages'],
                        'allow' => true,
                        'roles' => ['@']
                    ],
                ],
            ],
        ];
    }

    public function actionNotifications()
    {
        Yii::$app->response->format = Response::FORMAT_RAW;
        $this->setupSseHeaders();

        $toast = Yii::$app->toast;
        $redis = Yii::$app->redis;
        $channel = $toast->getСhannel();

        echo ": ready\n\n";
        flush();

        $subscriber = Yii::$app->redisNotifications;
        $subscriber->listen(
            $channel, 
            function($type, $channel, $message) {
                $this->sendEvent("[$message]");
            },
            function(\Throwable $error) {
                \Yii::error($error->getMessage());
            }
        );

        exit();
    }

    /**
     * Fallback на Long Polling для старых браузеров
     */
    public function actionMessages()
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        $messages = Yii::$app->toast->getMessages();
        if ($messages) {
            Yii::$app->toast->clearMessages();
        }
        return $messages;
    }

    public function sendEvent($data)
    {
        echo "data: $data\n\n";
        return @ob_flush() && @flush();
    }
}
