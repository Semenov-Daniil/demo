<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%students_competencies}}`.
 */
class m241014_103125_create_students_competencies_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%students_competencies}}', [
            'id' => $this->primaryKey(),
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%students_competencies}}');
    }
}
