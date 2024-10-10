<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%user_student_to_users}}`.
 */
class m241010_112409_create_user_student_to_users_table extends Migration
{
    const TABLE_NAME_USERS = '{{%users}}';
    const TABLE_NAME_PASSWORDS = '{{%passwords}}';
    
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->insert(self::TABLE_NAME_USERS, [
            'id' => 2,
            'login' => 'student',
            'password' => \Yii::$app->security->generatePasswordHash('student'),
            'surname' => 'Student',
            'name' => '1',
        ]);

        $this->insert(self::TABLE_NAME_PASSWORDS, [
            'users_id' => 2,
            'password' => 'student',
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->delete(self::TABLE_NAME_PASSWORDS, ['users_id' => 2]);
        $this->delete(self::TABLE_NAME_USERS, ['id' => 2]);
    }
}
