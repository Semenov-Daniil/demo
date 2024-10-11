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
            'title' => 'Название тестирования',
            'num_modules' => 'Кол-во модулей',
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

    /**
     * Add user
     * 
     * @param array $data 
     * @return array
    */
    public static function addTesting($data = []): array
    {
        $answer = [
            'status' => false,
            'model' => new Testings()
        ];

        $test = &$answer['model'];
        $test->scenario = Testings::SCENARIO_ADD;

        $test->load($data, '');
        $test->validate();

        if (!$test->hasErrors()) {
            $answer['status'] = $test->save();
        }

        return $answer;
    }
}
