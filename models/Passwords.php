<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "{{%passwords}}".
 *
 * @property string $password
 * @property int $users_id
 *
 * @property Users $users
 */
class Passwords extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%passwords}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['password', 'users_id'], 'required'],
            [['users_id'], 'integer'],
            [['password'], 'string', 'max' => 255],
            [['users_id'], 'exist', 'skipOnError' => true, 'targetClass' => Users::class, 'targetAttribute' => ['users_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'password' => 'Password',
            'users_id' => 'Users ID',
        ];
    }

    /**
     * Gets query for [[Users]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getUsers()
    {
        return $this->hasOne(Users::class, ['id' => 'users_id']);
    }

    public static function addPassword($data = [])
    {
        $model = new Passwords();
        
        $model->load($data, '');
        $model->validate();

        if (!$model->hasErrors()) {
            return $model->save();
        }
        
        return false;
    }
}
