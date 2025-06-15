<?php

use yii\db\Migration;

class m250615_210436_add_statuses extends Migration
{
    const TABLE_NAME = '{{%statuses}}';

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
                'configuring'
            ],
            [
                'ready'
            ],
            [
                'deleting'
            ],
            [
                'error'
            ],
        ]
    );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->delete(self::TABLE_NAME, ['title' => 'configuring']);
        $this->delete(self::TABLE_NAME, ['title' => 'ready']);
        $this->delete(self::TABLE_NAME, ['title' => 'deleting']);
        $this->delete(self::TABLE_NAME, ['title' => 'error']);
    }
}
