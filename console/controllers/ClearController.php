<?php

namespace console\controllers;

use common\models\Events;
use common\models\ExpertsEvents;
use common\models\Roles;
use common\models\StudentsEvents;
use common\models\Users;
use Yii;
use yii\console\Controller;
use yii\console\ExitCode;
use yii\helpers\Console;

class ClearController extends Controller
{
    public $defaultAction = 'clear-all';

    public function actionClearAll()
    {
        $this->clearEvents();
        $this->clearStudents();
        return ExitCode::OK;
    }

    public function actionClearEvents()
    {
        $this->clearEvents();
        return ExitCode::OK;
    }

    public function actionClearStudents()
    {
        $this->clearStudents();
        return ExitCode::OK;
    }

    public function clearEvents()
    {
        $events = Events::find()
            ->asArray()
            ->all()
        ;
        $expertsDir = [];

        foreach ($events as $event) {
            if (empty(Users::findOne(['id' => $event['experts_id']])) || !is_dir(Yii::getAlias('@events/' . $event['dir_title']))) {
                $event->delete();
                continue;
            }

            $expertsDir[] = $event['dir_title'];
        }

        $emptyDir = array_diff(scandir(Yii::getAlias('@events')), [...$expertsDir, '.', '..']);
        
        foreach ($emptyDir as $dir) {
            Yii::$app->fileComponent->removeDirectory(Yii::getAlias("@events/$dir"));
        }
    }

    public function clearStudents()
    {
        $students = Users::find()
            ->where(['roles_id' => Roles::getRoleId(Users::TITLE_ROLE_STUDENT)])
            ->all()
        ;
        $studentsDir = [];

        foreach ($students as $student) {
            $studentEvent = StudentsEvents::findOne(['students_id' => $student['id']]);

            if (empty($studentEvent) || !is_dir(Yii::getAlias('@students/' . $student['login']))) {
                $student->delete();
                continue;
            }

            $studentsDir[] = $student['login'];
        }
        
        $emptyDir = array_diff(scandir(Yii::getAlias('@students')), [...$studentsDir, '.', '..']);

        foreach ($emptyDir as $dir) {
            Yii::$app->fileComponent->removeDirectory(Yii::getAlias("@students/$dir"));
        }
    }
}
