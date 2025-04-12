<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%encrypted_passwords}}`.
 */
class m241018_113856_create_encrypted_passwords_table extends Migration
{
    const TABLE_NAME = '{{%encrypted_passwords}}';
    const USERS_TABLE_NAME = '{{%users}}';

    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable(self::TABLE_NAME, [
            'users_id' => $this->primaryKey(),
            'encrypted_password' => $this->string(255)->notNull(),
        ]);

        $this->addForeignKey('fk-encrypted_password-users_id', self::TABLE_NAME, 'users_id', self::USERS_TABLE_NAME, 'id', 'CASCADE', 'CASCADE');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropForeignKey('fk-encrypted_password-users_id', self::TABLE_NAME);
        
        $this->dropTable(self::TABLE_NAME);
    }
}
