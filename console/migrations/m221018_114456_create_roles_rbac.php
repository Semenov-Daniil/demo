<?php

use common\rbac\UserRoleRule;
use yii\db\Migration;

/**
 * Class m221018_114456_create_roles_rbac
 */
class m221018_114456_create_roles_rbac extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
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
        
        $superExpert = $auth->createRole('sExpert');
        $superExpert->description = 'Супер эксперт';
        $auth->add($superExpert);
        $auth->addChild($superExpert, $expert);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $auth = Yii::$app->authManager;

        $student = $auth->getRole('student');
        if ($student) {
            $auth->remove($student);
        }

        $expert = $auth->getRole('expert');
        if ($expert) {
            $auth->remove($expert);
        }

        $ruleName = (new UserRoleRule())->name;
        $rule = $auth->getRule($ruleName);
        if ($rule) {
            $auth->remove($rule);
        }
    }
}
