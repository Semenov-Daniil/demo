<?php

namespace app\models;

use Yii;
use yii\helpers\VarDumper;

/**
 * This is the model class for table "dm_competencies".
 *
 * @property int $users_id
 * @property string $title
 * @property int $num_modules
 *
 * @property User $users
 */
class Competencies extends \yii\db\ActiveRecord
{

    public function scenarios()
    {
        $scenarios = parent::scenarios();
        $scenarios[self::SCENARIO_DEFAULT] = ['title', 'num_modules', '!users_id'];
        return $scenarios;
    }

    public function afterSave($insert, $changedAttributes)
    {
        parent::afterSave($insert, $changedAttributes);

        if ($insert) {
            for($i = 0; $i < $this->num_modules; $i++) {
                $module = new Modules();
                $module->competencies_id = $this->users_id;
                $module->number = $i + 1;
                VarDumper::dump($module->save(), 10, true);
                // $module->save();
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%competencies}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['title', 'num_modules'], 'required'],
            [['num_modules'], 'integer', 'min' => 1],
            [['title'], 'string', 'max' => 255],
            [['users_id'], 'exist', 'skipOnError' => true, 'targetClass' => Users::class, 'targetAttribute' => ['users_id' => 'id']],
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
     * @return bool
    */
    public function addCompetencies(): bool
    {
        return $this->save();
    }
}
