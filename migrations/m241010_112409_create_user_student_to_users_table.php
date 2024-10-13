<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%user_student_to_users}}`.
 */
class m241010_112409_create_user_student_to_users_table extends Migration
{
    public array $role_student = [];
    public array $student = [];

    const TABLE_NAME_USERS = '{{%users}}';
    const TABLE_NAME_PASSWORDS = '{{%passwords}}';
    const PASSWORD = 'student';
    
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->role_student = (new \yii\db\Query())
            ->select('id')
            ->from('{{%roles}}')
            ->where(['title' => 'student'])
            ->one();

        $this->insert(self::TABLE_NAME_USERS, [
            'login' => 'student',
            'password' => \Yii::$app->security->generatePasswordHash(self::PASSWORD),
            'surname' => 'Student',
            'name' => '1',
            'roles_id' => $this->role_student['id']
        ]);

        $this->student = (new \yii\db\Query())
            ->select('id')
            ->from(self::TABLE_NAME_USERS)
            ->where(['login' => 'student'])
            ->one();

        $this->insert(self::TABLE_NAME_PASSWORDS, [
            'users_id' => $this->student['id'],
            'password' => self::PASSWORD,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->delete(self::TABLE_NAME_PASSWORDS, ['users_id' => $this->student['id']]);
        $this->delete(self::TABLE_NAME_USERS, ['id' => $this->student['id']]);
    }
}
