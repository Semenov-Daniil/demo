<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;
use yii\helpers\VarDumper;

/**
 * This is the model class for table "dm_competencies".
 *
 * @property int $experts_id
 * @property string $title
 *
 * @property Users $users
 * @property Modules[] $modules
 * @property StudentsCompetencies[] $studentsCompetencies
 */
class Competencies extends ActiveRecord
{
    public int $module_count = 1;

    public function scenarios()
    {
        $scenarios = parent::scenarios();
        $scenarios[self::SCENARIO_DEFAULT] = ['title', 'module_count', '!experts_id'];
        return $scenarios;
    }

    public function afterSave($insert, $changedAttributes)
    {
        parent::afterSave($insert, $changedAttributes);

        if ($insert) {
            for ($i = 0; $i < $this->module_count; $i++) {
                $module = new Modules(['competencies_id' => $this->experts_id]);
                $module->save();
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
            [['title', 'module_count'], 'required'],
            [['module_count'], 'integer', 'min' => 1],
            [['title'], 'string', 'max' => 255],
            [['experts_id'], 'exist', 'skipOnError' => true, 'targetClass' => Users::class, 'targetAttribute' => ['experts_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'experts_id' => 'Эксперт',
            'title' => 'Название тестирования',
            'module_count' => 'Кол-во модулей',
        ];
    }

    public function attributes() {
        return [
            ...parent::attributes(),
            'module_count',
        ];
    }

    /**
     * Gets query for [[Users]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getUsers()
    {
        return $this->hasOne(Users::class, ['id' => 'experts_id']);
    }

    /**
     * Gets query for [[Modules]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getModules()
    {
        return $this->hasMany(Modules::class, ['competencies_id' => 'experts_id']);
    }

    /**
     * Gets query for [[StudentsCompetencies]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getStudentsCompetencies()
    {
        return $this->hasMany(StudentsCompetencies::class, ['competencies_id' => 'experts_id']);
    }
}
