<?php

namespace common\components;

use Yii;
use yii\base\Component;

class ToastComponent extends Component
{
    private $redis;

    public function __construct()
    {
        parent::init();
        $this->redis = Yii::$app->redis;
    }

    public function addToast(string $message, string $type): void
    {
        $channel = self::getСhannel();
        $content = json_encode([
            'text' => $message,
            'type' => $type
        ]);
        Yii::$app->redis->publish($channel, $content);
    }

    public static function getСhannel(): string
    {
        try {
            return "notifications_user_" . Yii::$app->user->id;
        } catch (\Exception $e) {
            Yii::error("\nFailed to retrieve queue toast channel:\n{$e->getMessage()}", __METHOD__);
            throw $e;
        }
    }
}