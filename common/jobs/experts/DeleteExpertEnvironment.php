<?php

namespace common\jobs\experts;

use common\models\Statuses;
use common\models\Users;
use common\services\ExpertService;
use common\services\UserService;
use Yii;
use yii\base\BaseObject;
use yii\queue\JobInterface;

class DeleteExpertEnvironment extends BaseObject implements JobInterface
{
    public int|null $id = null;

    public function execute($queue)
    {
        $service = new ExpertService();
        $userService = new UserService();
        try {
            $service->deleteExpertEnvironment($this->id);
            if (!$userService->deleteUser($this->id)) throw new \Exception("Failed to delete user ($this->id)");
        } catch (\Exception $e) {
            Yii::error("\nError job delete expert evironment:\n{$e->getMessage()}", __METHOD__);
            Users::updateAll(['statuses_id' => Statuses::getStatusId(Statuses::ERROR)], ['id' => $this->id]);
            throw $e;
        }   
    }
}