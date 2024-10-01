<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%passwords}}`.
 */
class m241001_124557_create_password_admin_to_passwords_table extends Migration
{
    const TABLE_NAME = '{{%passwords}}';
    
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->insert(self::TABLE_NAME, [
            'id' => 1,
            'password' => 'admin',
            'users_id' => '1',
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
