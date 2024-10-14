<?php

namespace app\commands;

use app\models\Competencies;
use app\models\Users;
use app\models\UsersCompetencies;
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
        $model = new UsersCompetencies();
        
        $model->load([
            'surname' => 'Main',
            'name' => 'Expert',
            'title' => 'Сompetence',
            'num_modules' => 4
        ], '');
        
        if ($model->validate()) {
            $transaction = Yii::$app->db->beginTransaction();   
            try {
                $user = new Users();
                $user->attributes = $model->attributes;
                $user->addExpert();

                $competence = new Competencies();
                $competence->attributes = $model->attributes;
                $competence->users_id = $user->id;
                $competence->save();
                
                $transaction->commit();
                echo "\nExpert create\n";
                return ExitCode::OK;
            } catch(\Exception $e) {
                $transaction->rollBack();
            } catch(\Throwable $e) {
                $transaction->rollBack();
            }
        }

        return ExitCode::UNSPECIFIED_ERROR;
    }
}
