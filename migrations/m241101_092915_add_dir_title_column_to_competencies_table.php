<?php

use yii\db\Migration;

/**
 * Handles adding columns to table `{{%competencies}}`.
 */
class m241101_092915_add_dir_title_column_to_competencies_table extends Migration
{
    const TABLE_NAME = '{{%competencies}}';

    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn(self::TABLE_NAME, 'dir_title', $this->string(255)->notNull());
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn(self::TABLE_NAME, 'dir_title');
    }
}
