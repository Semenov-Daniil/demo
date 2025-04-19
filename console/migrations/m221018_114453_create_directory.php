<?php

use common\rbac\UserRoleRule;
use yii\db\Migration;

/**
 * Class m221018_114453_create_directory
 */
class m221018_114453_create_directory extends Migration
{
    private $directories = [
        '@logs',
        '@events',
        '@students',
    ];

    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $siteUser = Yii::$app->params['siteUser'];
        $siteGroup = Yii::$app->params['siteGroup'];

        try {
            foreach ($this->directories as $directory) {
                $directory = Yii::getAlias($directory);
                $output = shell_exec("echo ".Yii::$app->params['systemPassword']. " | sudo -S ".Yii::getAlias('@bash')."/create_dir.sh {$directory} {$siteUser} {$siteGroup} 2>&1");
                if ($output) {
                    throw new Exception("Failed to create directory: {$output}");
                }
            }
            return true;
        } catch (\Exception $e) {
            echo "Failed to create directories: " . $e->getMessage() . "\n";
            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m221018_114453_create_directory cannot be reverted.\n";
        return true;
    }
}
