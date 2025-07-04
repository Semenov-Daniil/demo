<?php

namespace common\jobs\events;

use common\models\Events;
use common\models\Statuses;
use common\services\EventService;
use common\services\StudentService;
use Yii;
use yii\base\BaseObject;
use yii\queue\JobInterface;

class DeleteEventEnvironment extends BaseObject implements JobInterface
{
    public int|null $eventId = null;
    public $initialStudentCount = 0;
    public $attempt = 0;

    public function execute($queue)
    {
        $maxAttempts = 120;
        $delayBetweenAttempts = 5;

        $event = Events::findOne(['id' => $this->eventId]);
        if (!$event) return true;
        
        if ($this->attempt === 0) {
            $studentIds = array_column($event->students, 'students_id');
            
            $this->initialStudentCount = count($studentIds);

            if ($this->initialStudentCount === 0) {
                $this->deleteEvent();
                return;
            }

            (new StudentService)->deleteStudents($studentIds);

            Yii::$app->queue->delay($delayBetweenAttempts)->push(new self([
                'eventId' => $this->eventId,
                'attempt' => $this->attempt + 1,
                'initialStudentCount' => $this->initialStudentCount,
            ]));
            return;
        }

        $remainingEvents = count($event->students);

        if ($remainingEvents === 0) {
            $this->deleteEvent();
        } elseif ($this->attempt < $maxAttempts) {
            Yii::$app->queue->delay($delayBetweenAttempts)->push(new self([
                'eventId' => $this->eventId,
                'attempt' => $this->attempt + 1,
                'initialStudentCount' => $this->initialStudentCount,
            ]));
        } else {
            Yii::error("Failed to delete event {$this->eventId}: timeout after $maxAttempts attempts", __METHOD__);
        }
    }

    private function deleteEvent()
    {
        $service = new EventService();
        try {
            if (!$service->deleteEvent($this->eventId)) throw new \Exception("Failed to delete event ($this->eventId)");
        } catch (\Exception $e) {
            Yii::error("\nError job delete event evironment:\n{$e->getMessage()}", __METHOD__);
            Events::updateAll(['statuses_id' => Statuses::getStatusId(Statuses::ERROR)], ['id' => $this->eventId]);
            throw $e;
        } 
    }
}