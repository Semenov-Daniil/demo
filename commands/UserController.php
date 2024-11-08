<?php

namespace app\commands;

use app\models\ExpertsCompetencies;
use yii\console\Controller;
use yii\console\ExitCode;
use yii\helpers\Console;

class UserController extends Controller
{
    /**
     * This command creates user expert.
     * 
     * @return int Exit code
     */
    public function actionCreateExpert(int $count = 1)
    {
        for ($i = 0; $i < $count; $i++) {
            $model = new ExpertsCompetencies();
            
            $model->load([
                'surname' => 'Main',
                'name' => 'Expert',
                'title' => 'Сompetence',
                'module_count' => 4
            ], '');
    
            if ($model->addExpert()) {
                $this->stdout("Expert №" . $i + 1 . " has been successfully created!\n", Console::BG_GREEN);
            } else {
                $this->stdout("Expert №" . $i + 1 . " has not been created!\n", Console::BG_RED);
            }
    
        }

        return ExitCode::OK;
    }
}
