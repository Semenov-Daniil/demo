<?php

namespace common\jobs\events;

use common\models\Events;
use common\models\Statuses;
use common\models\Users;
use common\services\EventService;
use common\services\ExpertService;
use common\services\UserService;
use Yii;
use yii\base\BaseObject;
use yii\queue\JobInterface;

class DeleteEventEnvironment extends BaseObject implements JobInterface
{
    public int|null $eventId = null;
    public $eventJobIds = [];
    public $attempt = 0;

    public function execute($queue)
    {
        $service = new EventService();
        try {
            $service->deleteEventEnvironment($this->eventId);
            if (!$service->deleteEvent($this->eventId)) throw new \Exception("Failed to delete event ($this->eventId)");
        } catch (\Exception $e) {
            Yii::error("\nError job delete event evironment:\n{$e->getMessage()}", __METHOD__);
            Events::updateAll(['statuses_id' => Statuses::getStatusId(Statuses::ERROR)], ['id' => $this->eventId]);
            throw $e;
        }   
    }

    // public function execute($queue)
    // {
    //     $maxAttempts = 120;
    //     $delayBetweenAttempts = 5;

    //     $expert =  Users::findOne(['id' => $this->expertId]);
    //     if (!$expert) return true;

    //     if (empty($this->eventJobIds)) {
    //         $eventIds = array_column($expert->events, 'id');
    //         foreach ($eventIds as $eventId) {
    //             $jobId = Yii::$app->queue->push(new DeleteEventEnvironment(['id' => $eventId]));
    //             if ($jobId) $this->eventJobIds[] = $jobId;
    //         }

    //         if (empty($this->eventJobIds)) {
    //             $this->deleteExpert();
    //             return;
    //         }

    //         Yii::$app->queue->delay($delayBetweenAttempts)->push(new self([
    //             'expertId' => $this->expertId,
    //             'eventJobIds' => $this->eventJobIds,
    //             'attempt' => $this->attempt + 1,
    //         ]));
    //         return;
    //     }

    //     if ($this->areAllEventJobsCompleted()) {
    //         $this->deleteExpert();
    //     } elseif ($this->attempt < $maxAttempts) {
    //         Yii::$app->queue->delay($delayBetweenAttempts)->push(new self([
    //             'expertId' => $this->expertId,
    //             'eventJobIds' => $this->eventJobIds,
    //             'attempt' => $this->attempt + 1,
    //         ]));
    //     } else {
    //         Yii::error("Failed to delete expert {$this->expertId}: timeout after $maxAttempts attempts", __METHOD__);
    //     }
    // }

    // private function areAllEventJobsCompleted()
    // {
    //     $queue = Yii::$app->queue;
    //     foreach ($this->eventJobIds as $jobId) {
    //         $isPending = $queue->isJobReserved($jobId) || $queue->isJobWaiting($jobId);
    //         if ($isPending) return false;
    //     }
    //     return true;
    // }

    // private function deleteExpert()
    // {
    //     $userService = new UserService();
    //     try {
    //         if (!$userService->deleteUser($this->id)) throw new \Exception("Failed to delete user ($this->id)");
    //     } catch (\Exception $e) {
    //         Yii::error("\nError job delete expert:\n{$e->getMessage()}", __METHOD__);
    //         Users::updateAll(['statuses_id' => Statuses::getStatusId(Statuses::ERROR)], ['id' => $this->id]);
    //     }
    // }
}