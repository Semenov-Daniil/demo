<?php

namespace common\jobs\modules;

use common\models\Modules;
use common\services\ModuleService;
use Yii;
use yii\base\BaseObject;
use yii\queue\JobInterface;

class ChangePrivilegesStudents extends BaseObject implements JobInterface
{
    public Modules|null $module = null;

    public function execute($queue)
    {
        $service = new ModuleService();
        try {
            $service->changePrivilegesStudents($this->module);
        } catch (\Exception $e) {
            Yii::error("\nError job change privileges student for module {$this->module->id}:\n" . $e->getMessage(), __METHOD__);
            $service->changeStatus($this->module, !$this->module->status);
            throw $e;
        }   
    }
}