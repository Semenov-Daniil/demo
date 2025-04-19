<?php

use common\rbac\UserRoleRule;
use yii\db\Migration;

/**
 * Class m221018_114454_setup_ssh
 */
class m221018_114454_setup_ssh extends Migration
{
    private $commands = [
        'bash', 'ls', 'unzip', 'cat', 'pwd', 'rm', 'mv', 'cp', 'mkdir', 'rmdir'
    ];

    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $logFile = Yii::getAlias('@logs/ssh.log');
        $commands = implode(',', $this->commands);
        try {
            $output = shell_exec("echo ".Yii::$app->params['systemPassword']. " | sudo -S ".Yii::getAlias('@bash')."/setup_ssh.sh \"{$commands}\" {$logFile} 2>&1");
            if ($output) {
                throw new Exception("Failed to setup SSH: {$output}");
            }
            return true;
        } catch (\Exception $e) {
            echo "Failed to setup SSH: " . $e->getMessage() . "\n";
            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m221018_114454_setup_ssh cannot be reverted.\n";
        return true;
    }
}
