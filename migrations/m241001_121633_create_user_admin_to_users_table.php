<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%users}}`.
 */
class m241001_121633_create_user_admin_to_users_table extends Migration
{
    const TABLE_NAME = '{{%users}}';
    
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->insert(self::TABLE_NAME, [
            'id' => 1,
            'login' => 'admin',
            'password' => \Yii::$app->security->generatePasswordHash('admin'),
            'surname' => 'Admin',
            'name' => '1',
            'roles_id' => '1',
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->delete(self::TABLE_NAME, ['id' => 1]);
    }
}
