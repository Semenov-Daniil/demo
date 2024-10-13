<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%competencies}}`.
 */
class m241010_121355_create_competencies_table extends Migration
{
    const TABLE_NAME = '{{%competencies}}';

    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable(self::TABLE_NAME, [
            'users_id' => $this->primaryKey(),
            'title' => $this->string(255)->notNull(),
            'num_modules' => $this->integer()->notNull()->defaultValue(1),
        ]);

        $this->addForeignKey('fk-competencies-users_id', self::TABLE_NAME, 'users_id', '{{%users}}', 'id', 'CASCADE', 'CASCADE');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropForeignKey('fk-competencies-users_id', self::TABLE_NAME);

        $this->dropTable(self::TABLE_NAME);
    }
}
