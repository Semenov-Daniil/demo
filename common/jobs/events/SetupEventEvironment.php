<?php

namespace common\jobs\events;

use common\services\EventService;
use Dotenv\Dotenv;
use Yii;
use yii\base\BaseObject;
use yii\queue\JobInterface;

class SetupEventEvironment extends BaseObject implements JobInterface
{
    public int|null $eventId = null;
    public string $eventDir = '';
    public int|null $countModules = null;

    public function execute($queue)
    {
        $service = new EventService();
        try {
            $service->createEventDirectories($this->eventDir);
            $service->createModulesEvent($this->eventId, $this->countModules);
        } catch (\Exception $e) {
            Yii::error("Error job set up event evironment: " . $e->getMessage(), __METHOD__);
            $service->deleteEvent($this->eventId);
            throw $e;
        }   
    }
}