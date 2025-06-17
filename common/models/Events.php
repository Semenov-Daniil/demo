<?php

namespace common\models;

use app\components\AppComponent;
use app\components\DbComponent;
use app\components\FileComponent;
use common\services\EventService;
use common\traits\RandomStringTrait;
use Yii;
use yii\data\ActiveDataProvider;
use yii\db\ActiveRecord;
use yii\db\Expression;
use yii\helpers\VarDumper;

/**
 * This is the model class for table "{{%events}}".
 *
 * @property int $id
 * @property int $experts_id
 * @property string $title
 * @property string $dir_title
 * @property string|null $created_at
 * @property string|null $updated_at
 * @property int $statuses_id
 *
 * @property Users $expert
 * @property Modules[] $modules
 * @property StudentsEvents[] $students
 * @property FilesEvents[] $files
 * @property Statuses $statuses
 */
class Events extends ActiveRecord
{
    const SCENARIO_UPDATE = 'update';

    public function scenarios()
    {
        $scenarios = parent::scenarios();
        $scenarios[self::SCENARIO_DEFAULT] = ['title', 'experts_id', '!dir_title', '!statuses_id'];
        $scenarios[self::SCENARIO_UPDATE] = ['title', 'experts_id', 'updated_at'];
        return $scenarios;
    }
    
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%events}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['title'], 'required'],
            [['experts_id'], 'integer'],
            [['title', 'dir_title'], 'string', 'max' => 255],
            [['experts_id'], 'exist', 'skipOnError' => true, 'targetClass' => Users::class, 'targetAttribute' => ['experts_id' => 'id']],
            [['title', 'dir_title'], 'trim'],
            ['experts_id', 'required', 'when' => function($model) {
                return Yii::$app->user->can('sExpert');
            }, 'message' => 'Необходимо выбрать эксперта.'],
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
            'experts_id' => 'Эксперт',
            'title' => 'Название события',
            'dir_title' => 'Название директории',
            'statuses_id' => 'Статус'
        ];
    }

    /**
     * Gets query for [[Users]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getExpert()
    {
        return $this->hasOne(Users::class, ['id' => 'experts_id'])->inverseOf('event');
    }

    /**
     * Gets query for [[Modules]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getModules()
    {
        return $this->hasMany(Modules::class, ['events_id' => 'id']);
    }

    /**
     * Gets query for [[StudentsEvents]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getStudents()
    {
        return $this->hasMany(Students::class, ['events_id' => 'id']);
    }

    /**
     * Gets query for [[Filesevents]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getFiles()
    {
        return $this->hasMany(Files::class, ['events_id' => 'id']);
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
     * Get DataProvider events
     * 
     * @param int $records number of records.
     * 
     * @return ActiveDataProvider
     */
    public static function getDataProviderEvents(int $expertId, int $records = 10): ActiveDataProvider
    {
        $query = self::find()
            ->select([
                self::tableName() . '.id',
                'expert' => 'CONCAT(surname, " ", name, COALESCE(CONCAT(" ", patronymic), ""))',
                'title',
                'countModules' => Modules::find()
                    ->select('COUNT(*)')
                    ->where(['events_id' => new Expression(self::tableName() . '.id')]),
            ])
            ->where([
                self::tableName() . '.statuses_id' => [
                    Statuses::getStatusId(Statuses::CONFIGURING),
                    Statuses::getStatusId(Statuses::READY),
                ]
            ])
            ->joinWith('expert', false)
            ->andFilterWhere(['experts_id' => Yii::$app->user->can('sExpert') ? null : $expertId])
        ;

        return new ActiveDataProvider([
            'query' => $query->asArray(),
            'pagination' => [
                'pageSize' => $records,
                'route' => 'event',
            ],
        ]);
    }

    public static function getEvents(int $expertId): array
    {
        return self::find()
            ->select('title')
            ->where(['experts_id' => $expertId])
            ->orderBy([
                'id' => SORT_ASC
            ])
            ->indexBy('id')
            ->column();
    }

    public static function getExpertEvents(): array
    {
        $events = self::find()
            ->select([
                'event_id' => self::tableName() . '.id',
                'event_title' => self::tableName() . '.title',
                'expert_name' => 'CONCAT(surname, " ", name, COALESCE(CONCAT(" ", patronymic), ""))',
            ])
            ->joinWith('expert')
            ->orderBy([
                'expert_name' => SORT_ASC,
                'event_title' => SORT_ASC
            ])
            ->asArray()
            ->all()
        ;
        
        $result = [];
        foreach ($events as $event) {
            $result[$event['expert_name']][$event['event_id']] = $event['event_title'];
        }

        return $result;
    }

    public static function getExpertsEvents(int|array|null $ids = null)
    {
        return self::find()
            ->select('experts_id')
            ->where(['id' => $ids])
            ->distinct()
            ->column()
        ;
    }
}
