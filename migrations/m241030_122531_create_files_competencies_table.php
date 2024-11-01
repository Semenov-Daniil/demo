<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%files_competencies}}`.
 */
class m241030_122531_create_files_competencies_table extends Migration
{
    const TABLE_NAME = '{{%files_competencies}}';
    const TABLE_NAME_COMPETENCIES = '{{%competencies}}';

    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%files_competencies}}', [
            'id' => $this->primaryKey(),
            'competencies_id' => $this->integer()->notNull(),
            'title' => $this->string(255)->notNull(),
        ]);

        $this->createIndex('files_competencies-competencies_id', self::TABLE_NAME, 'competencies_id');
        $this->addForeignKey('fk-files_competencies-competencies_id', self::TABLE_NAME, 'competencies_id', self::TABLE_NAME_COMPETENCIES, 'experts_id', 'CASCADE', 'CASCADE');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropForeignKey('fk-files_competencies-competencies_id', self::TABLE_NAME);
        $this->dropIndex('files_competencies-competencies_id', self::TABLE_NAME);

        $this->dropTable('{{%files_competencies}}');
    }
}
