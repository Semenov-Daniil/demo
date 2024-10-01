<?php

use yii\db\Migration;

/**
 * Handles adding columns to table `{{%passwords}}`.
 */
class m241001_101545_add_users_id_column_to_passwords_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('{{%passwords}}', 'users_id', $this->integer()->notNull());

        $this->createIndex('passwords-users_id', '{{%passwords}}', 'users_id');
        $this->addForeignKey('fk-passwords-users_id', '{{%passwords}}', 'users_id', '{{%users}}', 'id', 'RESTRICT', 'CASCADE');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropForeignKey('fk-passwords-users_id', '{{%passwords}}');
        $this->dropIndex('passwords-users_id', '{{%passwords}}');
        
        $this->dropColumn('{{%passwords}}', 'users_id');
    }
}
