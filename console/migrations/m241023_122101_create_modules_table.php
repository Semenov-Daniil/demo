<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%modules}}`.
 */
class m241023_122101_create_modules_table extends Migration
{
    const TABLE_NAME = '{{%modules}}';
    const EVENTS_TABLE_NAME = '{{%events}}';

    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable(self::TABLE_NAME, [
            'id' => $this->primaryKey(),
            'events_id' => $this->integer()->notNull(),
            'status' => $this->tinyInteger(1)->defaultValue(1)->notNull(),
            'number' => $this->integer()->notNull(),
        ]);

        $this->createIndex('idx-modules-events_id', self::TABLE_NAME, 'events_id');
        $this->addForeignKey('fk-modules-events_id', self::TABLE_NAME, 'events_id', self::EVENTS_TABLE_NAME, 'id', 'CASCADE', 'CASCADE');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropForeignKey('fk-modules-events_id', self::TABLE_NAME);
        $this->dropIndex('idx-modules-events_id', self::TABLE_NAME);

        $this->dropTable(self::TABLE_NAME);
    }
}
