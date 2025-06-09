<?php

namespace common\jobs\modules;

use common\models\Modules;
use common\services\ModuleService;
use Yii;
use yii\base\BaseObject;
use yii\queue\JobInterface;

class SetupModuleEvironment extends BaseObject implements JobInterface
{
    public Modules|null $module = null;

    public function execute($queue)
    {
        $service = new ModuleService();
        try {
            $service->setupModuleEnvironment($this->module);
        } catch (\Exception $e) {
            Yii::error("Error job set up module evironment: " . $e->getMessage(), __METHOD__);
            $service->deleteModule($this->module?->id);
            throw $e;
        }   
    }
}