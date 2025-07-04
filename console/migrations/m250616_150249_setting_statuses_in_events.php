<?php

use yii\db\Migration;

class m250616_150249_setting_statuses_in_events extends Migration
{
    const TABLE_NAME = '{{%events}}';
    const STATUSES_TABLE_NAME = '{{%statuses}}';

    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn(self::TABLE_NAME, 'statuses_id', $this->integer()->notNull());

        $this->createIndex('idx-events-statuses_id', self::TABLE_NAME, 'statuses_id');
        $this->addForeignKey('fk-events-statuses_id', self::TABLE_NAME, 'statuses_id', self::STATUSES_TABLE_NAME, 'id', 'RESTRICT', 'CASCADE');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropForeignKey('fk-events-statuses_id', self::TABLE_NAME);
        $this->dropIndex('idx-events-statuses_id', self::TABLE_NAME);
        
        $this->dropColumn(self::TABLE_NAME, 'statuses_id');
    }
}
