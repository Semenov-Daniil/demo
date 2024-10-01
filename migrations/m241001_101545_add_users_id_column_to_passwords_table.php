<?php

use yii\db\Migration;

/**
 * Handles adding columns to table `{{%passwords}}`.
 */
class m241001_101545_add_users_id_column_to_passwords_table extends Migration
{
    const TABLE_NAME = '{{%passwords}}';
    
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn(self::TABLE_NAME, 'users_id', $this->integer()->notNull());

        $this->createIndex('passwords-users_id', self::TABLE_NAME, 'users_id');
        $this->addForeignKey('fk-passwords-users_id', self::TABLE_NAME, 'users_id', '{{%users}}', 'id', 'CASCADE', 'CASCADE');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropForeignKey('fk-passwords-users_id', self::TABLE_NAME);
        $this->dropIndex('passwords-users_id', self::TABLE_NAME);
        
        $this->dropColumn(self::TABLE_NAME, 'users_id');
    }
}
