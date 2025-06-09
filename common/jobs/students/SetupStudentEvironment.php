<?php

namespace common\jobs\students;

use common\models\Students;
use common\services\StudentService;
use Dotenv\Dotenv;
use Yii;
use yii\base\BaseObject;
use yii\queue\JobInterface;

class SetupStudentEvironment extends BaseObject implements JobInterface
{
    public Students|null $student = null;
    public string $login = '';
    public string $password = '';

    public function execute($queue)
    {
        $service = new StudentService();
        try {
            $service->setupStudentEnvironment($this->student, $this->login, $this->password);
        } catch (\Exception $e) {
            Yii::error("Error job set up student evironment: " . $e->getMessage(), __METHOD__);
            $service->deleteStudent($this->student?->students_id);
            throw $e;
        }   
    }
}