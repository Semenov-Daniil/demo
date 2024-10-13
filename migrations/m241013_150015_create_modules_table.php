<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%modules}}`.
 */
class m241013_150015_create_modules_table extends Migration
{
    const TABLE_NAME_MODULES = '{{%modules}}';
    const TABLE_NAME_COMPETENCIES = '{{%competencies}}';

    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%modules}}', [
            'id' => $this->primaryKey(),
            'competencies_id' => $this->integer()->notNull(),
            'title' => $this->string(255)->unique()->notNull(),
        ]);

        $this->createIndex('modules-competencies_id', self::TABLE_NAME_MODULES, 'competencies_id');
        $this->addForeignKey('fk-modules-competencies_id', self::TABLE_NAME_MODULES, 'competencies_id', self::TABLE_NAME_COMPETENCIES, 'users_id', 'CASCADE', 'CASCADE');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropForeignKey('fk-modules-competencies_id', self::TABLE_NAME_MODULES);
        $this->dropIndex('modules-competencies_id', self::TABLE_NAME_MODULES);

        $this->dropTable(self::TABLE_NAME_MODULES);
    }
}
