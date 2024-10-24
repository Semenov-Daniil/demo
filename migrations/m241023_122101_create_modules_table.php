<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%modules}}`.
 */
class m241023_122101_create_modules_table extends Migration
{
    const TABLE_NAME = '{{%modules}}';
    const TABLE_NAME_USERS = '{{%users}}';
    const TABLE_NAME_COMPETENCIES = '{{%competencies}}';

    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable(self::TABLE_NAME, [
            'id' => $this->primaryKey(),
            'competencies_id' => $this->integer()->notNull(),
            'status' => $this->tinyInteger(1)->defaultValue(1),
        ]);

        $this->createIndex('modules-competencies_id', self::TABLE_NAME, 'competencies_id');
        $this->addForeignKey('fk-modules-competencies_id', self::TABLE_NAME, 'competencies_id', self::TABLE_NAME_COMPETENCIES, 'experts_id', 'CASCADE', 'CASCADE');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropForeignKey('fk-modules-competencies_id', self::TABLE_NAME);
        $this->dropIndex('modules-competencies_id', self::TABLE_NAME);

        $this->dropTable(self::TABLE_NAME);
    }
}
