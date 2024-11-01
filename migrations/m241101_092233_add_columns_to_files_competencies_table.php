<?php

use yii\db\Migration;

/**
 * Handles adding columns to table `{{%files_competencies}}`.
 */
class m241101_092233_add_columns_to_files_competencies_table extends Migration
{
    const TABLE_NAME = '{{%files_competencies}}';

    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn(self::TABLE_NAME, 'filename', $this->string(255)->notNull());
        $this->addColumn(self::TABLE_NAME, 'extension', $this->string(255)->notNull());
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn(self::TABLE_NAME, 'extension');
        $this->dropColumn(self::TABLE_NAME, 'filename');
    }
}
