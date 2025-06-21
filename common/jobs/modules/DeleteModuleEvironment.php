<?php

namespace common\jobs\modules;

use common\models\Modules;
use common\models\Statuses;
use common\services\ModuleService;
use Yii;
use yii\base\BaseObject;
use yii\queue\JobInterface;

class DeleteModuleEvironment extends BaseObject implements JobInterface
{
    public int|null $moduleId = null;

    public function execute($queue)
    {
        $service = new ModuleService();
        try {
            $service->deleteModule($this->moduleId);
        } catch (\Exception $e) {
            Yii::error("Error job delete module evironment: " . $e->getMessage(), __METHOD__);
            Modules::updateAll(['statuses_id' => Statuses::getStatusId(Statuses::ERROR)], ['id' => $this->moduleId]);
            throw $e;
        }   
    }
}