<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "dm_testings".
 *
 * @property int $users_id
 * @property string $title
 * @property int $num_modules
 *
 * @property User $users
 */
class Testings extends \yii\db\ActiveRecord
{
    const SCENARIO_ADD = 'add';

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'dm_testings';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['title'], 'required'],
            [['num_modules'], 'integer', 'min' => 1],
            [['title'], 'string', 'max' => 255],
            [['users_id'], 'exist', 'skipOnError' => true, 'targetClass' => Users::class, 'targetAttribute' => ['users_id' => 'id']],

            [['users_id'], 'required', 'on' => self::SCENARIO_ADD],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'users_id' => 'Users ID',
            'title' => 'Title',
            'num_modules' => 'Num Modules',
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

    public static function addTesting($data = [])
    {
        $model = new Testings();

        $model->scenario = Testings::SCENARIO_ADD;

        $model->load($data, '');
        $model->validate();

        if (!$model->hasErrors()) {
            return $model->save();
        }

        return false;
    }
}
