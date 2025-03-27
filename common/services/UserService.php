<?php

namespace common\services;

use common\models\Roles;
use common\models\Users;
use common\traits\RandomStringTrait;
use Exception;
use Yii;

class UserService
{
    use RandomStringTrait;

    /**
     * Creates a base user with the specified role and attributes.
     * @param string $role
     * @param array $attributes
     * @return Users
     * @throws Exception
     */
    public function createBaseUser(string $role, array $attributes): Users
    {
        $user = new Users();
        $user->attributes = $attributes;
        $user->roles_id = Roles::getRoleId($role);
        $user->temp_password = $this->generateRandomString(6, ['lowercase', 'uppercase', 'digits']);
        $user->password = Yii::$app->security->generatePasswordHash($user->temp_password);
        $user->login = $this->generateUniqueLogin(8, ['lowercase']);
        $user->auth_key = Yii::$app->security->generateRandomString();

        if (!$user->save()) {
            throw new Exception('Failed to save user');
        }
        return $user;
    }

    /**
     * Deletes a user by ID, preventing self-deletion.
     * 
     * @param int $id
     * @return bool
     * @throws Exception
     */
    public function deleteUser(?int $id): bool
    {
        if ($id === null) {
            return false;
        }

        $transaction = Yii::$app->db->beginTransaction();
        try {
            $user = Users::findOne($id);
            if ($user && $user->id !== Yii::$app->user->id && $user->delete()) {
                $transaction->commit();
                return true;
            }
            $transaction->rollBack();
            return false;
        } catch (Exception $e) {
            $transaction->rollBack();
            throw $e;
        }
    }

    /**
     * Generates a unique value for the login.
     * 
     * @param int $length Length of the generated string
     * @param array $charSets Character sets for random string generation
     * 
     * @return string
     */
    public function generateUniqueLogin(int $length = 32, array $charSets = []): string
    {
        $attr = $this->generateRandomString($length, $charSets);
    
        while(Users::find()->where(['login' => $attr])->exists()) {
            $attr = $this->generateRandomString($length, $charSets);
        }

        return $attr;
    }
}