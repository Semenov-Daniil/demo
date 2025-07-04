<?php

use common\rbac\UserRoleRule;
use yii\db\Migration;
use yii\helpers\Console;

/**
 * Class m221018_114454_setup_services
 */
class m221018_114454_setup_services extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        try {
            $output = Yii::$app->commandComponent->executeBashScript(Yii::getAlias('@bash/setup/setup.sh'));
            if ($output['returnCode']) {
                throw new Exception("\nFailed to setup services:\n{$output['stderr']}\n{$output['stdout']}");
            }
            return true;
        } catch (\Exception $e) {
            echo "{$e->getMessage()}\n";
            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "Setup services cannot be reverted.\n";
        return true;
    }
}
