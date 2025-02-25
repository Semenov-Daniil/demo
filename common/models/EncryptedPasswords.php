<?php

namespace common\models;

use Exception;
use Yii;

/**
 * This is the model class for table "{{%encrypted_passwords}}".
 *
 * @property string $encrypted_password
 * @property int $users_id
 *
 * @property Users $user
 */
class EncryptedPasswords extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%encrypted_passwords}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['encrypted_password', 'users_id'], 'required'],
            [['users_id'], 'integer'],
            [['encrypted_password'], 'string', 'max' => 255],
            [['users_id'], 'exist', 'skipOnError' => true, 'targetClass' => Users::class, 'targetAttribute' => ['users_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'encrypted_password' => 'Пароль',
            'users_id' => 'Пользователь',
        ];
    }

    /**
     * Gets query for [[Users]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getUser()
    {
        return $this->hasOne(Users::class, ['id' => 'users_id'])->inverseOf('encryptedPassword');
    }

    /**
     * Encrypts and stores a password for a user.
     * 
     * @param int $userId The ID of the user.
     * @param string $password The plaintext password to be encrypted and stored.
     * @return bool Returns `true` if the password is successfully saved.
     * @throws Exception Throws an exception if the password fails to save.
     */
    public static function addEncryptedPassword(int $userId, string $password): bool
    {
        $model = new EncryptedPasswords();

        $model->users_id = $userId;
        $model->encrypted_password = base64_encode(Yii::$app->security->encryptByPassword($password, Yii::$app->params['encryptionKey']));

        if ($model->save()) {
            return true;
        }

        throw new Exception('Failed to save password');
    }

    /**
     * Encrypts a password using Yii2's security component and encodes it in base64.
     *
     * This function takes a plaintext password, encrypts it using Yii2's `encryptByPassword` method,
     * and then encodes the result in base64 for safe storage or transmission.
     *
     * @param string $password The plaintext password to be encrypted.
     * @return string Returns the base64-encoded encrypted password.
     */
    public static function encryptByPassword(string $password): string
    {
        return base64_encode(Yii::$app->security->encryptByPassword($password, Yii::$app->params['encryptionKey']));
    }

    /**
     * Decrypts a base64-encoded password using Yii2's security component.
     *
     * This function takes a base64-encoded encrypted password, decodes it, and then decrypts it
     * using Yii2's `decryptByPassword` method with the application's encryption key.
     *
     * @param string $password The base64-encoded encrypted password to be decrypted.
     * @return string Returns the decrypted plaintext password.
     */
    public static function decryptByPassword(string $password): string
    {
        return Yii::$app->security->decryptByPassword((base64_decode($password)), Yii::$app->params['encryptionKey']);
    }
}
