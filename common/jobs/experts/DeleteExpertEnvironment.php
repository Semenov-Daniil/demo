<?php

namespace common\jobs\experts;

use common\jobs\events\DeleteEventEnvironment;
use common\models\Statuses;
use common\models\Users;
use common\services\EventService;
use common\services\ExpertService;
use common\services\UserService;
use Yii;
use yii\base\BaseObject;
use yii\queue\JobInterface;

class DeleteExpertEnvironment extends BaseObject implements JobInterface
{
    public int|null $expertId = null;
    public $initialEventCount = 0;
    public $attempt = 0;

    public function execute($queue)
    {
        $maxAttempts = 120;
        $delayBetweenAttempts = 5;

        $expert =  Users::findOne(['id' => $this->expertId]);
        if (!$expert) return true;
        
        if ($this->attempt === 0) {
            $eventIds = array_column($expert->events, 'id');
            $this->initialEventCount = count($eventIds);

            if ($this->initialEventCount === 0) {
                $this->deleteExpert();
                return;
            }

            (new EventService)->deleteEvents($eventIds);

            Yii::$app->queue->delay($delayBetweenAttempts)->push(new self([
                'expertId' => $this->expertId,
                'attempt' => $this->attempt + 1,
                'initialEventCount' => $this->initialEventCount,
            ]));
            return;
        }

        $remainingEvents = count($expert->events);

        if ($remainingEvents === 0) {
            $this->deleteExpert();
        } elseif ($this->attempt < $maxAttempts) {
            Yii::$app->queue->delay($delayBetweenAttempts)->push(new self([
                'expertId' => $this->expertId,
                'attempt' => $this->attempt + 1,
                'initialEventCount' => $this->initialEventCount,
            ]));
        } else {
            Yii::error("Failed to delete expert {$this->expertId}: timeout after $maxAttempts attempts", __METHOD__);
        }
    }

    private function deleteExpert()
    {
        $userService = new UserService();
        try {
            if (!$userService->deleteUser($this->expertId)) throw new \Exception("Failed to delete user ($this->expertId)");
        } catch (\Exception $e) {
            Yii::error("\nError job delete expert:\n{$e->getMessage()}", __METHOD__);
            Users::updateAll(['statuses_id' => Statuses::getStatusId(Statuses::ERROR)], ['id' => $this->expertId]);
        }
    }
}