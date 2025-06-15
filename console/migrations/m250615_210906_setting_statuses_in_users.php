<?php

use yii\db\Migration;

class m250615_210906_setting_statuses_in_users extends Migration
{
    const TABLE_NAME = '{{%users}}';
    const STATUSES_TABLE_NAME = '{{%statuses}}';

    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn(self::TABLE_NAME, 'statuses_id', $this->integer()->notNull());

        $this->createIndex('idx-users-statuses_id', self::TABLE_NAME, 'statuses_id');
        $this->addForeignKey('fk-users-statuses_id', self::TABLE_NAME, 'statuses_id', self::STATUSES_TABLE_NAME, 'id', 'RESTRICT', 'CASCADE');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropForeignKey('fk-users-statuses_id', self::TABLE_NAME);
        $this->dropIndex('idx-users-statuses_id', self::TABLE_NAME);
        
        $this->dropColumn(self::TABLE_NAME, 'statuses_id');
    }
}
