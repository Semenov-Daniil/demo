<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%students_events}}`.
 */
class m241018_114026_create_students_events_table extends Migration
{
    const TABLE_NAME = '{{%students_events}}';
    const TABLE_NAME_USERS = '{{%users}}';
    const TABLE_NAME_EVENTS = '{{%events}}';

    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable(self::TABLE_NAME, [
            'students_id' => $this->primaryKey(),
            'events_id' => $this->integer()->notNull(),
            'dir_prefix' => $this->string(255)->unique()->notNull(),
        ]);

        $this->addForeignKey('fk-students_events-students_id', self::TABLE_NAME, 'students_id', self::TABLE_NAME_USERS, 'id', 'CASCADE', 'CASCADE');

        $this->createIndex('idx-students_events-events_id', self::TABLE_NAME, 'events_id');
        $this->addForeignKey('fk-students_events-events_id', self::TABLE_NAME, 'events_id', self::TABLE_NAME_EVENTS, 'id', 'CASCADE', 'CASCADE');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropForeignKey('fk-students_events-students_id', self::TABLE_NAME);
        $this->dropForeignKey('fk-students_events-events_id', self::TABLE_NAME);
        $this->dropIndex('idx-students_events-events_id', self::TABLE_NAME);

        $this->dropTable(self::TABLE_NAME);
    }
}
