<?php

use yii\db\Migration;

/**
 * Handles adding columns to table `{{%roles}}`.
 */
class m241001_064255_add_default_roles_to_roles_table extends Migration
{
    const TABLE_NAME = '{{%roles}}';

    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->batchInsert(self::TABLE_NAME, 
        [
            'title'
        ], 
        [
            [
                'student'
            ],
            [
                'expert'
            ]
        ]
    );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->delete(self::TABLE_NAME, ['title' => 'student']);
        $this->delete(self::TABLE_NAME, ['title' => 'expert']);
    }
}
