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

        if (!$user->save()) throw new Exception('Failed to save user');

        return $user;
    }

    public function updateUser(int $id, array $attributes): bool
    {
        $transaction = Yii::$app->db->beginTransaction();
        try {
            $user = Users::findOne(['id' => $id]);
            $lastUpdate = $user->updated_at;

            $user->attributes = $attributes;

            if (strtotime($lastUpdate) > strtotime($user->updated_at)) throw new \yii\db\StaleObjectException("Attempt to update old data");

            if (!$user->update()) throw new Exception("Failed to save the user");
            $transaction->commit();
            return true;
        } catch (\yii\db\StaleObjectException $e) {
            $transaction->rollBack();
            Yii::warning("Attempt to update old data user '$user->id'");
            Yii::$app->toast->addToast(
                'Устаревшие данные эксперта. Пожалуйста, обновите форму.',
                'info'
            );
            return false;
        } catch (\Exception $e) {
            $transaction->rollBack();
            Yii::error("\nFailed to update the user '$user->id':\n{$e->getMessage()}", __METHOD__);
            return false;
        }
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
        if (!$id || !($user = Users::findOne(['id' => $id]))) {
            return false;
        }

        $transaction = Yii::$app->db->beginTransaction();
        try {
            $userId = (Yii::$app instanceof \yii\web\Application ? Yii::$app->user->id : null);
            if ($user->id === $userId) throw new Exception("Attempting to delete the current user ($id)");
            if (!$user->delete()) throw new Exception("Failed delete user {$user->id}");
            $transaction->commit();
            return true;
        } catch (Exception $e) {
            $transaction->rollBack();
            Yii::error("\nError delete user:\n{$e->getMessage()}", __METHOD__);
            return false;
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