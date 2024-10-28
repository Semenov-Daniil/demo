<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%competencies}}`.
 */
class m241018_113956_create_competencies_table extends Migration
{
    const TABLE_NAME = '{{%competencies}}';

    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable(self::TABLE_NAME, [
            'experts_id' => $this->primaryKey(),
            'title' => $this->string(255)->notNull(),
        ]);

        $this->addForeignKey('fk-competencies-experts_id', self::TABLE_NAME, 'experts_id', '{{%users}}', 'id', 'CASCADE', 'CASCADE');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropForeignKey('fk-competencies-experts_id', self::TABLE_NAME);

        $this->dropTable(self::TABLE_NAME);
    }
}
