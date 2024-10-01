<?php

use yii\db\Migration;

/**
 * Handles adding columns to table `{{%users}}`.
 */
class m241001_064509_add_roles_id_column_to_users_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('{{%users}}', 'roles_id', $this->integer()->notNull());

        $this->createIndex('users-roles_id', '{{%users}}', 'roles_id');
        $this->addForeignKey('fk-users-roles_id', '{{%users}}', 'roles_id', '{{%roles}}', 'id', 'RESTRICT', 'CASCADE');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropForeignKey('fk-users-roles_id', '{{%users}}');
        $this->dropIndex('users-roles_id', '{{%users}}');
        
        $this->dropColumn('{{%users}}', 'roles_id');
    }
}
