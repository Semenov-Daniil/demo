<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%files_events}}`.
 */
class m241030_122531_create_files_events_table extends Migration
{
    const TABLE_NAME = '{{%files_events}}';
    const EVENTS_TABLE_NAME = '{{%events}}';

    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%files_events}}', [
            'id' => $this->primaryKey(),
            'events_id' => $this->integer()->notNull(),
            'save_name' => $this->string(255)->notNull(),
            'origin_name' => $this->string(255)->notNull(),
            'extension' => $this->string(255)->notNull(),
            'type' => $this->string(255)->notNull(),
        ]);

        $this->createIndex('idx-files_events-events_id', self::TABLE_NAME, 'events_id');
        $this->addForeignKey('fk-files_events-events_id', self::TABLE_NAME, 'events_id', self::EVENTS_TABLE_NAME, 'id', 'CASCADE', 'CASCADE');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropForeignKey('fk-files_events-events_id', self::TABLE_NAME);
        $this->dropIndex('idx-files_events-events_id', self::TABLE_NAME);

        $this->dropTable('{{%files_events}}');
    }
}
