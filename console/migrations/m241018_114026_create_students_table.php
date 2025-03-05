<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%students}}`.
 */
class m241018_114026_create_students_table extends Migration
{
    const TABLE_NAME = '{{%students}}';
    const USERS_TABLE_NAME = '{{%users}}';
    const EVENTS_TABLE_NAME = '{{%events}}';

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

        $this->addForeignKey('fk-students-students_id', self::TABLE_NAME, 'students_id', self::USERS_TABLE_NAME, 'id', 'CASCADE', 'CASCADE');

        $this->createIndex('idx-students-events_id', self::TABLE_NAME, 'events_id');
        $this->addForeignKey('fk-students-events_id', self::TABLE_NAME, 'events_id', self::EVENTS_TABLE_NAME, 'id', 'CASCADE', 'CASCADE');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropForeignKey('fk-students-students_id', self::TABLE_NAME);
        $this->dropForeignKey('fk-students-events_id', self::TABLE_NAME);
        $this->dropIndex('idx-students-events_id', self::TABLE_NAME);

        $this->dropTable(self::TABLE_NAME);
    }
}
