<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%users}}`.
 */
class m241018_113822_create_users_table extends Migration
{
    const TABLE_NAME = '{{%users}}';
    const TABLE_NAME_ROLES = '{{%roles}}';
    
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable(self::TABLE_NAME, [
            'id' => $this->primaryKey(),
            'surname' => $this->string(255)->notNull(),
            'name' => $this->string(255)->notNull(),
            'patronymic' => $this->string(255)->defaultValue(null),
            'login' => $this->string(255)->notNull()->unique(),
            'password' => $this->string(255)->notNull(),
            'roles_id' => $this->integer()->notNull(),
            'auth_key' => $this->string(32)->unique()->notNull(),
        ]);

        $this->createIndex('idx-users-roles_id', self::TABLE_NAME, 'roles_id');
        $this->addForeignKey('fk-users-roles_id', self::TABLE_NAME, 'roles_id', self::TABLE_NAME_ROLES, 'id', 'RESTRICT', 'CASCADE');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropForeignKey('fk-users-roles_id', self::TABLE_NAME);
        $this->dropIndex('idx-users-roles_id', self::TABLE_NAME);
        
        $this->dropTable(self::TABLE_NAME);
    }
}
