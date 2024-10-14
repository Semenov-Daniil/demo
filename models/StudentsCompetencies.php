<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "dm_students_competencies".
 *
 * @property int $students_id
 * @property int $competencies_id
 *
 * @property Competencies $competencies
 * @property Users $students
 */
class StudentsCompetencies extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'dm_students_competencies';
    }

    public function scenarios()
    {
        $scenarios = parent::scenarios();
        $scenarios[self::SCENARIO_DEFAULT] = ['!students_id', '!competencies_id'];
        return $scenarios;
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['students_id', 'competencies_id'], 'required'],
            [['students_id', 'competencies_id'], 'integer'],
            [['competencies_id'], 'exist', 'skipOnError' => true, 'targetClass' => Competencies::class, 'targetAttribute' => ['competencies_id' => 'users_id']],
            [['students_id'], 'exist', 'skipOnError' => true, 'targetClass' => Users::class, 'targetAttribute' => ['students_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'students_id' => 'Students ID',
            'competencies_id' => 'Competencies ID',
        ];
    }

    /**
     * Gets query for [[Competencies]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getCompetencies()
    {
        return $this->hasOne(Competencies::class, ['users_id' => 'competencies_id']);
    }

    /**
     * Gets query for [[Students]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getStudents()
    {
        return $this->hasOne(Users::class, ['id' => 'students_id']);
    }
}
