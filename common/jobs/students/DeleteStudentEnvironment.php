<?php

namespace common\jobs\students;

use common\models\Statuses;
use common\models\Students;
use common\services\StudentService;
use Yii;
use yii\base\BaseObject;
use yii\queue\JobInterface;

class DeleteStudentEnvironment extends BaseObject implements JobInterface
{
    public int|null $studentId = null;

    public function execute($queue)
    {
        $service = new StudentService();
        try {
            $service->deleteStudent($this->studentId);
        } catch (\Exception $e) {
            Yii::error("\nError job delete student evironment:\n{$e->getMessage()}", __METHOD__);
            Students::updateAll(['statuses_id' => Statuses::getStatusId(Statuses::ERROR)], ['id' => $this->studentId]);
            throw $e;
        }
    }
}