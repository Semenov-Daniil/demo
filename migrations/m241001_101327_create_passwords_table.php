<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%passwords}}`.
 */
class m241001_101327_create_passwords_table extends Migration
{
    const TABLE_NAME = '{{%passwords}}';
    const TABLE_NAME_USERS = '{{%users}}';

    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable(self::TABLE_NAME, [
            'users_id' => $this->primaryKey(),
            'password' => $this->string(255)->notNull(),
        ]);

        $this->addForeignKey('fk-passwords-users_id', self::TABLE_NAME, 'users_id', self::TABLE_NAME_USERS, 'id', 'CASCADE', 'CASCADE');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropForeignKey('fk-passwords-users_id', self::TABLE_NAME);
        
        $this->dropTable(self::TABLE_NAME);
    }
}
