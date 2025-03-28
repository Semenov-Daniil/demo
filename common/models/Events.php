<?php

namespace common\models;

use app\components\AppComponent;
use app\components\DbComponent;
use app\components\FileComponent;
use common\traits\RandomStringTrait;
use Yii;
use yii\data\ActiveDataProvider;
use yii\db\ActiveRecord;
use yii\helpers\VarDumper;

/**
 * This is the model class for table "dm_events".
 *
 * @property int $id
 * @property int $experts_id
 * @property string $title
 * @property string $dir_title
 *
 * @property Users $expert
 * @property Modules[] $modules
 * @property StudentsEvents[] $students
 * @property FilesEvents[] $files
 */
class Events extends ActiveRecord
{
    use RandomStringTrait;

    const SCENARIO_UPDATE = 'update';

    public int $countModules = 1;

    public function scenarios()
    {
        $scenarios = parent::scenarios();
        $scenarios[self::SCENARIO_UPDATE] = ['title', 'experts_id'];
        $scenarios[self::SCENARIO_DEFAULT] = ['title', 'countModules', '!experts_id', '!dir_title'];
        return $scenarios;
    }

    public function beforeSave($insert)
    {
        if (parent::beforeSave($insert)) {
            if ($this->isNewRecord) {
                $this->dir_title = $this->getUniqueStr('dir_title', 8, ['lowercase']);

                return Yii::$app->fileComponent->createDirectory(Yii::getAlias("@events/$this->dir_title"));
            }
            return true;
        } else {
            return false;
        }
    }

    public function afterSave($insert, $changedAttributes)
    {
        parent::afterSave($insert, $changedAttributes);

        if ($insert) {
            for ($i = 0; $i < $this->countModules; $i++) {
                $module = new Modules(['events_id' => $this->id]);
                $module->save();
                $this->createDirectoriesModule($module);
            }
        }
    }

    public function beforeDelete()
    {
        if (!parent::beforeDelete()) {
            return false;
        }

        return Students::deleteStudentsEvent($this->id) && Events::removeDirectory($this->id);
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
            [['title', 'countModules'], 'required'],
            [['countModules'], 'integer', 'min' => 1],
            [['experts_id'], 'integer'],
            [['title', 'dir_title'], 'string', 'max' => 255],
            [['experts_id'], 'exist', 'skipOnError' => true, 'targetClass' => Users::class, 'targetAttribute' => ['experts_id' => 'id']],
            [['title', 'dir_title'], 'trim'],
            ['experts_id', 'required', 'when' => function($model) {
                return Yii::$app->user->can('sExpert');
            }, 'message' => 'Необходимо выбрать эксперта.']
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
            'countModules' => 'Кол-во модулей',
            'dir_title' => 'Название директории'
        ];
    }

    public function attributes() {
        return [
            ...parent::attributes(),
            'countModules',
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
     * Find event id by expert.
     *
     * @param int $expertID id expert
     * @return int|null id event
     */
    public static function getIdByExpert(int $expertID): int|null
    {
        return self::findOne(['experts_id' => $expertID])?->id;
    }

    // public static function encryptById(int $id): string
    // {
    //     return base64_encode(Yii::$app->security->encryptByKey($id, Yii::$app->params['eventKey']));
    // }

    // public static function decryptById(string $id): string
    // {
    //     return Yii::$app->security->decryptByKey(base64_decode($id), Yii::$app->params['eventKey']);
    // }

    /**
     * Find event by expert.
     *
     * @param int $expertId id expert
     * @return static|null id event
     */
    public static function getEventByExpert(int $expertId): static|null
    {
        return self::findOne(['experts_id' => $expertId]);
    }

    /**
     * @param string $attr the name of the attribute to set a unique string value
     * @param int $length the length string
     * @param array $charSets An array of character sets to generate a string. Each element is a string of characters.
     * 
     * @return string a unique string is returned for this attribute.
     */
    public function getUniqueStr(string $attr, int $length = 32, array $charSets = []): string
    {
        $str = $charSets ? $this->generateRandomString($length, $charSets) : Yii::$app->security->generateRandomString($length);
    
        while(!Yii::$app->dbComponent->isUniqueValue(self::class, $attr, $str)) {
            $str = $charSets ? $this->generateRandomString($length, $charSets) : Yii::$app->security->generateRandomString($length);
        }

        return $str;
    }

    /**
     * Get DataProvider events
     * 
     * @param int $records number of records.
     * 
     * @return ActiveDataProvider
     */
    public static function getDataProviderEvents(int $expertID, int $records = 10): ActiveDataProvider
    {
        $subQuery = Modules::find()
            ->select('COUNT(*)')
            ->where(Modules::tableName() . '.events_id = ' . Events::tableName() . '.id');

        $query = self::find()
            ->select([
                self::tableName() . '.id',
                'CONCAT(surname, \' \', name, COALESCE(CONCAT(\' \', patronymic), \'\')) AS expert',
                'title',
                'countModules' => $subQuery
            ])
            ->joinWith('expert', false)
        ;

        if (!Yii::$app->user->can('sExpert')) {
            $query->andWhere(['experts_id' => $expertID]);
        }

        return new ActiveDataProvider([
            'query' => $query->asArray(),
            'pagination' => [
                'pageSize' => $records,
                'route' => 'events',
            ],
        ]);
    }

    public static function getEvents(int $expertID): array
    {
        return self::find()
            ->select('title')
            ->where(['experts_id' => $expertID])
            ->orderBy([
                'id' => SORT_ASC
            ])
            ->indexBy('id')
            ->column()
        ;
    }

    public static function getExpertEvents(): array
    {
        $events = self::find()
            ->select([
                self::tableName() . '.id AS event_id',
                self::tableName() . '.title AS event_title',
                'CONCAT('.Users::tableName().'.surname, \' \', '.Users::tableName().'.name, COALESCE(CONCAT(\' \', '.Users::tableName().'.patronymic), \'\')) AS expert_name'
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
            $expertName = $event['expert_name'];
            $eventId = $event['event_id'];
            $eventTitle = $event['event_title'];
            
            if (!isset($result[$expertName])) {
                $result[$expertName] = [];
            }
            $result[$expertName][$eventId] = $eventTitle;
        }

        return $result;
    }

    public static function getDirectoryModuleFileTitle(int|string $numberModule): string
    {
        return "module-{$numberModule}";
    }

    public function createDirectoriesModule($module)
    {
        return Yii::$app->fileComponent->createDirectory(Yii::getAlias("@events/$this->dir_title/" . $this->getDirectoryModuleFileTitle($module->number)));
    }

    public static function removeDirectory(int|array|null $eventsID)
    {
        $events = self::findAll(['id' => $eventsID]);

        foreach ($events as $event) {
            Yii::$app->fileComponent->removeDirectory(Yii::getAlias('@events/') . $event->dir_title);
        }

        return true;
    }

    /**
     * Deletes the events.
     * 
     * @return bool Returns the value `true` if the event was successfully deleted.
     */
    public static function deleteEvent(string|int $id): bool
    {
        $transaction = Yii::$app->db->beginTransaction();  

        try {
            $event = self::findOne(['id' => $id]);

            if (!empty($event) && $event->delete()) {
                $transaction->commit();
                return true;
            }

            $transaction->rollBack();
        } catch(\Exception $e) {
            $transaction->rollBack();
            Yii::$app->fileComponent->createDirectory(Yii::getAlias("@events/$event?->dir_title"));
            VarDumper::dump($e, 10, true);die;
        } catch(\Throwable $e) {
            $transaction->rollBack();
            Yii::$app->fileComponent->createDirectory(Yii::getAlias("@events/$event?->dir_title"));
        }

        return false;
    }

    public static function deleteEvents(array $eventsID): bool
    {
        foreach ($eventsID as $id) {
            if (!self::deleteEvent($id)) {
                return false;
            }
        }

        return true;
    }
}
