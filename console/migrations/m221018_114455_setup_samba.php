<?php

use common\rbac\UserRoleRule;
use yii\db\Migration;

/**
 * Class m221018_114455_setup_samba
 */
class m221018_114455_setup_samba extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $studentsPath = Yii::getAlias('@students');
        $logFile = Yii::getAlias('@logs/samba.log');
        try {
            $output = shell_exec("sudo ".Yii::getAlias('@bash')."/setup_samba.sh {$studentsPath} {$logFile} 2>&1");
            if ($output) {
                throw new Exception("Failed to setup Samba: {$output}");
            }
            return true;
        } catch (\Exception $e) {
            echo "Failed to setup Samba: " . $e->getMessage() . "\n";
            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m241018_123456_setup_samba cannot be reverted.\n";
        return true;
    }
}
