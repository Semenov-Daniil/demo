<?php

use yii\db\Migration;

/**
 * Class m241001_064255_add_default_roles_to_roles_table
 */
class m241001_064255_add_default_roles_to_roles_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->batchInsert('{{%roles}}', 
        [
            'id',
            'title'
        ], 
        [
            [
                1,
                'Admin'
            ],
            [
                2,
                'Student'
            ]
        ]
    );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->delete('{{%roles}}', ['id' => 1]);
        $this->delete('{{%roles}}', ['id' => 2]);
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m241001_064255_add_default_roles_to_roles_table cannot be reverted.\n";

        return false;
    }
    */
}
