<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%files}}`.
 */
class m241030_122531_create_files_table extends Migration
{
    const TABLE_NAME = '{{%files}}';
    const EVENTS_TABLE_NAME = '{{%events}}';
    const MODULES_TABLE_NAME = '{{%modules}}';

    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%files}}', [
            'id' => $this->primaryKey(),
            'events_id' => $this->integer()->notNull(),
            'modules_id' => $this->integer()->null()->defaultValue(null),
            'name' => $this->string(255)->notNull(),
            'extension' => $this->string(255)->notNull(),
        ]);

        $this->createIndex('idx-files-events_id', self::TABLE_NAME, 'events_id');
        $this->addForeignKey('fk-files-events_id', self::TABLE_NAME, 'events_id', self::EVENTS_TABLE_NAME, 'id', 'CASCADE', 'CASCADE');

        $this->createIndex('idx-files-modules_id', self::TABLE_NAME, 'modules_id');
        $this->addForeignKey('fk-files-modules_id', self::TABLE_NAME, 'modules_id', self::MODULES_TABLE_NAME, 'id', 'CASCADE', 'CASCADE');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropForeignKey('fk-files-events_id', self::TABLE_NAME);
        $this->dropIndex('idx-files-events_id', self::TABLE_NAME);
        
        $this->dropForeignKey('fk-files-modules_id', self::TABLE_NAME);
        $this->dropIndex('idx-files-modules_id', self::TABLE_NAME);

        $this->dropTable('{{%files}}');
    }
}
