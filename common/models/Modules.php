<?php

namespace common\models;

use app\components\DbComponent;
use common\modules\flash\Module;
use common\services\ModuleService;
use common\services\VirtualHostService;
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
 * @property string|null $created_at
 * @property string|null $updated_at
 * @property int $statuses_id
 *
 * @property Events $event
 * @property Statuses $statuses
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
            [['events_id'], 'required', 'message' => 'Необходимо выбрать событие.'],
            [['events_id', 'number'], 'integer'],
            ['status', 'boolean'],
            ['status', 'default', 'value' => true],
            [['events_id'], 'exist', 'skipOnError' => true, 'targetClass' => Events::class, 'targetAttribute' => ['events_id' => 'id']],
            // [['events_id', 'number'], 'unique', 'targetAttribute' => ['events_id', 'number'], 'message' => 'Несоответсвующий номер модуля.'],
            [['statuses_id'], 'exist', 'skipOnError' => true, 'targetClass' => Statuses::class, 'targetAttribute' => ['statuses_id' => 'id']],
            ['statuses_id', 'default', 'value' => Statuses::getStatusId(Statuses::CONFIGURING)],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'events_id' => 'Событие',
            'status' => 'Статус',
            'number' => 'Номер модуля',
        ];
    }

    public function beforeSave($insert)
    {
        if (!parent::beforeSave($insert)) {
            return false;
        }

        if ($this->isNewRecord) {
            $this->number = $this->getNextNumber();
        }

        return true;
    }

    private function getNextNumber()
    {
        $transaction = Yii::$app->db->beginTransaction();
        try {
            $maxNumber = static::find()
                ->where([
                    'events_id' => $this->events_id,
                    'statuses_id' => [
                        Statuses::getStatusId(Statuses::CONFIGURING),
                        Statuses::getStatusId(Statuses::READY),
                    ]
                ])
                ->max('number')
            ;
    
            $transaction->commit();
            return $maxNumber ? $maxNumber + 1 : 1;
        } catch (\Exception $e) {
            $transaction->rollBack();
            throw $e;
        }
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
     * Gets query for [[Statuses]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getStatuses()
    {
        return $this->hasOne(Statuses::class, ['id' => 'statuses_id']);
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
                ->select([self::tableName() . '.id', 'status', 'number', 'events_id'])
                ->joinWith('event', false)
                ->where([
                    'events_id' => $eventId,
                    self::tableName() . '.statuses_id' => [
                        Statuses::getStatusId(Statuses::CONFIGURING),
                        Statuses::getStatusId(Statuses::READY),
                    ],
                    Events::tableName() . '.statuses_id' => [
                        Statuses::getStatusId(Statuses::CONFIGURING),
                        Statuses::getStatusId(Statuses::READY),
                    ]
                ])
            ,
            'pagination' => [
                'pageSize' => $records,
                'route' => 'modules',
            ],
        ]);
    }
    
    /**
     * Get modules for student
     * 
     * @return array
     */
    public static function getModulesStudent(object $student): array
    {
        $modules = self::find()
            ->select(['number', 'events_id'])
            ->joinWith('event', false)
            ->where([
                'events_id' => $student->event->id,
                'status' => true,
                self::tableName() . '.statuses_id' => [
                    Statuses::getStatusId(Statuses::CONFIGURING),
                    Statuses::getStatusId(Statuses::READY),
                ],
                Events::tableName() . '.statuses_id' => [
                    Statuses::getStatusId(Statuses::CONFIGURING),
                    Statuses::getStatusId(Statuses::READY),
                ]
            ])
            ->asArray()
            ->all()
        ;

        foreach ($modules as &$module) {
            $module['domain'] = VirtualHostService::getDomain(ModuleService::getTitleDirectoryModule($student->dir_prefix, $module['number']));
        }
        unset($module);

        return $modules;
    }


}
