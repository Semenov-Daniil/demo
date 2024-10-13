<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%users}}`.
 */
class m241001_121633_create_user_expert_to_users_table extends Migration
{
    public array $role_expert = [];
    public array $expert = [];

    const TABLE_NAME_USERS = '{{%users}}';
    const TABLE_NAME_PASSWORDS = '{{%passwords}}';
    const PASSWORD = 'expert';
    
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->role_expert = (new \yii\db\Query())
            ->select('id')
            ->from('{{%roles}}')
            ->where(['title' => 'expert'])
            ->one();

        $this->insert(self::TABLE_NAME_USERS, [
            'login' => 'expert',
            'password' => \Yii::$app->security->generatePasswordHash(self::PASSWORD),
            'surname' => 'Expert',
            'name' => '1',
            'auth_key' => \Yii::$app->security->generateRandomString(),
            'roles_id' => $this->role_expert['id'],
        ]);

        $this->expert = (new \yii\db\Query())
            ->select('id')
            ->from(self::TABLE_NAME_USERS)
            ->where(['login' => 'expert'])
            ->one();

        $this->insert(self::TABLE_NAME_PASSWORDS, [
            'users_id' => $this->expert['id'],
            'password' => 'expert',
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->delete(self::TABLE_NAME_PASSWORDS, ['users_id' => $this->expert['id']]);
        $this->delete(self::TABLE_NAME_USERS, ['id' => $this->expert['id']]);
    }
}
