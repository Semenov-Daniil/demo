<?php

namespace console\controllers;

use common\models\Events;
use common\models\ExpertsEvents;
use common\models\Roles;
use common\models\Statuses;
use common\models\StudentsEvents;
use common\models\Users;
use common\services\EventService;
use common\services\ExpertService;
use common\services\StudentService;
use Exception;
use Yii;
use yii\console\Controller;
use yii\console\ExitCode;
use yii\helpers\Console;

class ServiceController extends Controller
{
    /**
     * Verify that all dependencies are installed and running.
     */
    public function actionSetup()
    {
        try {
            $output = Yii::$app->commandComponent->executeBashScript(Yii::getAlias('@bash/setup/setup.sh'));
            if ($output['returnCode']) {
                throw new Exception("\nFailed to setup services:\n{$output['stderr']}\n{$output['stdout']}");
            }
            
            $this->stdout("All dependencies are installed and running!\n", Console::FG_GREEN);
            return ExitCode::OK;
        } catch (\Exception $e) {
            $this->stderr($e->getMessage() . "\n", Console::FG_RED, Console::UNDERLINE);
            return ExitCode::UNSPECIFIED_ERROR;
        }
    }

    /**
     * Deleting inactive users and related data
     */
    public function actionClear()
    {
        $experts = Users::find()
            ->select(['id'])
            ->where([
                'roles_id' => Roles::getRoleId('expert'),
                'statuses_id' => Statuses::getStatusId(Statuses::ERROR),
                'statuses_id' => Statuses::getStatusId(Statuses::DELETING),
            ])
            ->column()
        ;

        (new ExpertService())->deleteExperts($experts);
        
        $events = Events::find()
            ->select(['id'])
            ->where([
                'statuses_id' => Statuses::getStatusId(Statuses::ERROR),
                'statuses_id' => Statuses::getStatusId(Statuses::DELETING),
            ])
            ->column()
        ;    

        (new EventService())->deleteEvents($events);

        $students = Users::find()
            ->select(['id'])
            ->where([
                'roles_id' => Roles::getRoleId('students'),
                'statuses_id' => Statuses::getStatusId(Statuses::ERROR),
                'statuses_id' => Statuses::getStatusId(Statuses::DELETING),
            ])
            ->column()
        ;

        (new StudentService())->deleteStudents($students);

        return ExitCode::OK;
    }
}
