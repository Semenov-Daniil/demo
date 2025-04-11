<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%events}}`.
 */
class m241018_113956_create_events_table extends Migration
{
    const TABLE_NAME = '{{%events}}';
    const USERS_TABLE_NAME = '{{%users}}';

    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable(self::TABLE_NAME, [
            'id' => $this->primaryKey(),
            'experts_id' => $this->integer()->notNull(),
            'title' => $this->string(255)->notNull(),
            'dir_title' => $this->string(255)->notNull(),
            'created_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP'),
            'updated_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'),
        ]);

        $this->addForeignKey('fk-events-experts_id', self::TABLE_NAME, 'experts_id', self::USERS_TABLE_NAME, 'id', 'CASCADE', 'CASCADE');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropForeignKey('fk-events-experts_id', self::TABLE_NAME);

        $this->dropTable(self::TABLE_NAME);
    }
}
