<?php

namespace app\models;

use Yii;
use yii\data\ActiveDataProvider;

/**
 * This is the model class for table "dm_modules".
 *
 * @property int $id
 * @property int $competencies_id
 * @property int|null $status
 *
 * @property Competencies $competencies
 */
class Modules extends \yii\db\ActiveRecord
{
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
            [['competencies_id', 'status'], 'integer'],
            ['status', 'default', 'value' => 1],
            [['competencies_id'], 'exist', 'skipOnError' => true, 'targetClass' => Competencies::class, 'targetAttribute' => ['competencies_id' => 'experts_id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'competencies_id' => 'Компетенция',
            'status' => 'Статус',
        ];
    }

    /**
     * Gets query for [[Competencies]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getCompetencies()
    {
        return $this->hasOne(Competencies::class, ['experts_id' => 'competencies_id']);
    }

    /**
     * Get DataProvider experts
     * 
     * @return array
     */
    public static function getDataProviderModules()
    {
        return new ActiveDataProvider([
            'query' => Modules::find()
                ->select(['id', 'status'])
                ,
        ]);
    }

    public function toggleStatus()
    {
        $this->status = $this->status ? 0 : 1; // Или другая логика переключения, если требуется
        return $this->save();
    }
}
