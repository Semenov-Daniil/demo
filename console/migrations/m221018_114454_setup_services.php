<?php

use common\rbac\UserRoleRule;
use yii\db\Migration;

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
        $logFile = 'services.log';

        try {
            $output = Yii::$app->commandComponent->executeBashScript(Yii::getAlias('@bash/utils/check_services.sh'), ['-y', "--log={$logFile}"]);
            if ($output['returnCode']) {
                throw new Exception("Failed to setup services: {$output['stderr']}");
            }
            
            return true;
        } catch (\Exception $e) {
            echo $e->getMessage() . "\n";
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
