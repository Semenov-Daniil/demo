<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%students_competencies}}`.
 */
class m241018_114026_create_students_competencies_table extends Migration
{
    const TABLE_NAME = '{{%students_competencies}}';
    const TABLE_NAME_USERS = '{{%users}}';
    const TABLE_NAME_COMPETENCIES = '{{%competencies}}';

    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable(self::TABLE_NAME, [
            'students_id' => $this->primaryKey(),
            'competencies_id' => $this->integer()->notNull(),
            'dir_title' => $this->string(255)->unique()->notNull(),
        ]);

        $this->addForeignKey('fk-students_competencies-students_id', self::TABLE_NAME, 'students_id', self::TABLE_NAME_USERS, 'id', 'CASCADE', 'CASCADE');

        $this->createIndex('students_competencies-competencies_id', self::TABLE_NAME, 'competencies_id');
        $this->addForeignKey('fk-students_competencies-competencies_id', self::TABLE_NAME, 'competencies_id', self::TABLE_NAME_COMPETENCIES, 'experts_id', 'CASCADE', 'CASCADE');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropForeignKey('fk-students_competencies-students_id', self::TABLE_NAME);
        $this->dropForeignKey('fk-students_competencies-competencies_id', self::TABLE_NAME);
        $this->dropIndex('students_competencies-competencies_id', self::TABLE_NAME);

        $this->dropTable(self::TABLE_NAME);
    }
}
