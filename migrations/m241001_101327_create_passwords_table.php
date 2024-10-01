<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%passwords}}`.
 */
class m241001_101327_create_passwords_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%passwords}}', [
            'id' => $this->primaryKey(),
            'password' => $this->string(255)->notNull(),
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%passwords}}');
    }
}
