<?php

namespace common\components;

class RedisSubscriber extends \yii\redis\Connection
{
    public function readMessage()
    {
        return parent::readResponse();
    }
}