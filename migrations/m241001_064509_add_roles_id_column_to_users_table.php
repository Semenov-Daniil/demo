<?php

use yii\db\Migration;

/**
 * Handles adding columns to table `{{%users}}`.
 */
class m241001_064509_add_roles_id_column_to_users_table extends Migration
{
    const TABLE_NAME = '{{%users}}';
    const TABLE_NAME_ROLES = '{{%roles}}';

    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn(self::TABLE_NAME, 'roles_id', $this->integer()->notNull());

        $this->createIndex('users-roles_id', self::TABLE_NAME, 'roles_id');
        $this->addForeignKey('fk-users-roles_id', self::TABLE_NAME, 'roles_id', self::TABLE_NAME_ROLES, 'id', 'RESTRICT', 'CASCADE');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropForeignKey('fk-users-roles_id', self::TABLE_NAME);
        $this->dropIndex('users-roles_id', self::TABLE_NAME);
        
        $this->dropColumn(self::TABLE_NAME, 'roles_id');
    }
}
