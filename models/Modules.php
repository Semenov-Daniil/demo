<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "dm_modules".
 *
 * @property int $id
 * @property int $competencies_id
 * @property string $title
 *
 * @property Competencies $competencies
 */
class Modules extends \yii\db\ActiveRecord
{
    public int $number = 0;

    public function scenarios()
    {
        $scenarios = parent::scenarios();
        $scenarios[self::SCENARIO_DEFAULT] = ['!competencies_id', '!title'];
        return $scenarios;
    }

    public function beforeSave($insert)
    {
        if (parent::beforeSave($insert)) {
            if ($this->isNewRecord) {
                $this->title = 'demo_' . Yii::$app->generate->generateRandomString() . '_m' . $this->number;
                Yii::$app->db->createCommand('CREATE DATABASE ' . $this->title)
                    ->execute();
            }
            return true;
        } else {
            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'dm_modules';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['competencies_id'], 'required'],
            [['competencies_id'], 'integer'],
            [['title'], 'string', 'max' => 255],
            [['title'], 'unique'],
            [['competencies_id'], 'exist', 'skipOnError' => true, 'targetClass' => Competencies::class, 'targetAttribute' => ['competencies_id' => 'users_id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'competencies_id' => 'Competencies ID',
            'title' => 'Title',
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
}
