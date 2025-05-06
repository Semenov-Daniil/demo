<?php

namespace console\controllers;

use common\models\Events;
use common\models\ExpertsEvents;
use common\models\Roles;
use common\models\StudentsEvents;
use common\models\Users;
use Exception;
use Yii;
use yii\console\Controller;
use yii\console\ExitCode;
use yii\helpers\Console;

class ServiceController extends Controller
{
    public $defaultAction = 'check';

    /**
     * Verify that all dependencies are installed and running.
     */
    public function actionCheck()
    {
        $logFile = 'services.log';

        try {
            $output = Yii::$app->commandComponent->executeBashScript(Yii::getAlias('@bash/utils/check_services.sh'), ['-y', "--log={$logFile}"]);
            
            if ($output['returnCode']) {
                throw new Exception("Failed to setup services: {$output['stderr']}");
            }
            
            $this->stdout("All dependencies are installed and running!\n", Console::FG_GREEN);
            return ExitCode::OK;
        } catch (\Exception $e) {
            $this->stderr($e->getMessage() . "\n", Console::FG_RED, Console::UNDERLINE);
            return ExitCode::UNSPECIFIED_ERROR;
        }
    }
}
