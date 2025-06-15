<?php

namespace common\components;

use common\traits\SseTrait;
use Yii;
use yii\base\Component;
use yii\web\Response;

class SseComponent extends Component
{
    use SseTrait;

    private $redis;
    private $redisSubscriber;

    const EXPERT_CHANNEL = 'yii_expert_channel';
    const EVENT_CHANNEL = 'yii_event_channel';
    const STUDENT_CHANNEL = 'yii_student_channel';
    const MODULE_CHANNEL = 'yii_module_channel';
    const FILE_CHANNEL = 'yii_file_channel';

    public function __construct()
    {
        parent::init();
        $this->redis = Yii::$app->redis;
        $this->redisSubscriber = Yii::$app->redisSubscriber;
    }

    public function publish(string $channel, string $message): void
    {
        $this->redis->publish($channel, json_encode([
            'message' => $message,
            'hasUpdates' => true,
            'timeout' => time()
        ]));
    }

    public function subscriber(string $channel)
    {
        Yii::$app->response->format = Response::FORMAT_RAW;
        $this->setupSseHeaders();

        echo ": ready\n\n";
        flush();

        $this->redisSubscriber->listen(
            $channel, 
            function($type, $channel, $message) {
                $this->sendEvent($message);
            },
            function(\Throwable $error) {
                Yii::error($error->getMessage());
            }
        );

        exit();
    }

    public function sendEvent($data)
    {
        echo "data: $data\n\n";
        return @ob_flush() && @flush();
    }
}