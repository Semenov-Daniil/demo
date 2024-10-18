<?php

namespace app\commands;

use app\models\Competencies;
use app\models\ExpertsCompetencies;
use app\models\Users;
use Yii;
use yii\console\Controller;
use yii\console\ExitCode;

class UserController extends Controller
{
    public $defaultAction = 'init';

    /**
     * This command creates user expert.
     * 
     * @return int Exit code
     */
    public function actionCreateExpert()
    {
        $model = new ExpertsCompetencies();
        
        $model->load([
            'surname' => 'Main',
            'name' => 'Expert',
            'title' => 'Сompetence',
            'num_modules' => 4
        ], '');

        if ($model->addExpert()) {
            echo "\nExpert create\n";
            return ExitCode::OK;
        }

        return ExitCode::UNSPECIFIED_ERROR;
    }
}
