<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%users}}`.
 */
class m241001_121633_create_user_expert_to_users_table extends Migration
{
    const TABLE_NAME = '{{%users}}';
    
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->insert(self::TABLE_NAME, [
            'id' => 1,
            'login' => 'expert',
            'password' => \Yii::$app->security->generatePasswordHash('expert'),
            'surname' => 'Expert',
            'name' => '1',
            'roles_id' => '2',
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
