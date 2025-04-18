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
        try {
            foreach ($this->directories as $directory) {
                if (!Yii::$app->fileComponent->createDirectory(Yii::getAlias($directory))) {
                    throw new Exception('Failed to create directory: ' . Yii::getAlias($directory));
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
