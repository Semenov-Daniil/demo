<?php

namespace app\commands;

use app\rbac\UserRoleRule;
use Yii;
use yii\console\Controller;
use yii\console\ExitCode;

class RbacController extends Controller
{

    /**
     * This command creates default roles.
     * 
     * @return int Exit code
     */
    public function actionInit(): int
    {
        $auth = Yii::$app->authManager;

        $rule = new UserRoleRule();
        $auth->add($rule);

        $student = $auth->createRole('student');
        $student->description = 'Студент';
        $student->ruleName = $rule->name;
        $auth->add($student);

        $expert = $auth->createRole('expert');
        $expert->description = 'Эксперт';
        $expert->ruleName = $rule->name;
        $auth->add($expert);

        return ExitCode::OK;
    }
}
