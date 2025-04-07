<?php

namespace common\models;

use app\components\DbComponent;
use common\modules\flash\Module;
use Exception;
use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use yii\helpers\VarDumper;
use yii\web\YiiAsset;

/**
 * This is the model class for table "dm_modules".
 *
 * @property int $id
 * @property int $events_id
 * @property int $status
 * @property int $number
 *
 * @property Events $event
 */
class Modules extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%modules}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['events_id'], 'required', 'message' => 'Необходимо выбрать чемпионат.'],
            [['events_id', 'number'], 'integer'],
            ['status', 'boolean'],
            ['status', 'default', 'value' => true],
            [['events_id'], 'exist', 'skipOnError' => true, 'targetClass' => Events::class, 'targetAttribute' => ['events_id' => 'id']],
            [['events_id', 'number'], 'unique', 'targetAttribute' => ['events_id', 'number'], 'message' => 'Несоответсвующий номер модуля.'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'events_id' => 'Чемпионат',
            'status' => 'Статус',
            'number' => 'Номер модуля',
        ];
    }

    public function beforeSave($insert)
    {
        if (parent::beforeSave($insert)) {
            if ($insert) {
                if (empty($this->number)) {
                    $this->number = $this->getNextNumber();
                }
            }
            return true;
        }
        return false;
    }

    private function getNextNumber()
    {
        $maxNumber = static::find()
            ->where(['events_id' => $this->events_id])
            ->max('number');

        return $maxNumber ? $maxNumber + 1 : 1;
    }

    /**
     * Gets query for [[Events]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getEvent()
    {
        return $this->hasOne(Events::class, ['id' => 'events_id']);
    }

    /**
     * Get ActiveDataProvider modules
     * 
     * @return ActiveDataProvider
     */
    public static function getDataProviderModules(?int $eventId = null, int $records = 10): ActiveDataProvider
    {
        return new ActiveDataProvider([
            'query' => Modules::find()
                ->select(['id', 'status', 'number'])
                ->where(['events_id' => $eventId])
            ,
            'pagination' => [
                'pageSize' => $records,
                'route' => 'modules',
            ],
        ]);
    }
}
