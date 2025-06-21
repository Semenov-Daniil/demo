<?php

use yii\db\Migration;

class m250621_105026_setting_statuses_in_modules extends Migration
{
    const TABLE_NAME = '{{%modules}}';
    const STATUSES_TABLE_NAME = '{{%statuses}}';

    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn(self::TABLE_NAME, 'statuses_id', $this->integer()->notNull());

        $this->createIndex('idx-modules-statuses_id', self::TABLE_NAME, 'statuses_id');
        $this->addForeignKey('fk-modules-statuses_id', self::TABLE_NAME, 'statuses_id', self::STATUSES_TABLE_NAME, 'id', 'RESTRICT', 'CASCADE');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropForeignKey('fk-modules-statuses_id', self::TABLE_NAME);
        $this->dropIndex('idx-modules-statuses_id', self::TABLE_NAME);
        
        $this->dropColumn(self::TABLE_NAME, 'statuses_id');
    }
}
