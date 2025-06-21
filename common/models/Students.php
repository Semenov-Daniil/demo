<?php

namespace common\models;

use app\components\AppComponent;
use app\components\DbComponent;
use app\components\FileComponent;
use common\services\ModuleService;
use common\services\StudentService;
use common\services\VirtualHostService;
use common\traits\RandomStringTrait;
use Exception;
use Yii;
use yii\data\ActiveDataProvider;
use yii\db\ActiveRecord;
use yii\helpers\VarDumper;

/**
 * This is the model class for table "dm_students".
 *
 * @property int $students_id
 * @property int $events_id
 * @property string $dir_prefix
 *
 * @property Events $event
 * @property Modules[] $modules
 * @property Users $user
 * @property EncryptedPasswords $encryptedPassword
 */
class Students extends ActiveRecord
{
    const TITLE_ROLE_STUDENT = "student";

    /**
     * {@inheritdoc}
     */
    public static function tableName(): string
    {
        return '{{%students}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules(): array
    {
        return [
            [['students_id', 'events_id', 'dir_prefix'], 'required'],
            [['dir_prefix'], 'string', 'max' => 255],
            [['dir_prefix'], 'trim'],
            [['students_id', 'events_id'], 'integer'],
            [['events_id'], 'exist', 'skipOnError' => true, 'targetClass' => Events::class, 'targetAttribute' => ['events_id' => 'id']],
            [['students_id'], 'exist', 'skipOnError' => true, 'targetClass' => Users::class, 'targetAttribute' => ['students_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'students_id' => 'Студент',
            'events_id' => 'Событие',
            'dir_prefix' => 'Директория',
        ];
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
     * Gets query for [[Modules]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getModules()
    {
        return $this->hasMany(Modules::class, ['events_id' => 'events_id']);
    }

    /**
     * Gets query for [[Users]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getUser()
    {
        return $this->hasOne(Users::class, ['id' => 'students_id']);
    }

    /**
     * Gets query for [[Passwords]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getEncryptedPassword(): object
    {
        return $this->hasOne(EncryptedPasswords::class, ['users_id' => 'students_id'])->inverseOf('user');
    }

    /**
     * Get DataProvider students
     * 
     * @param int $records number of records.
     * 
     * @return ActiveDataProvider
     */
    public static function getDataProviderStudents(string|int|null $eventId = null, bool $withDirectories = false, string $route = 'student', int $records = 10): ActiveDataProvider
    {
        $query = self::find()
            ->select([
                'students_id',
                self::tableName() . '.events_id',
                'fullName' => 'CONCAT(surname, " ", name, COALESCE(CONCAT(" ", patronymic), ""))',
                'login',
                'password' => EncryptedPasswords::tableName() . '.encrypted_password',
                'dir_prefix'
            ])
            ->joinWith('encryptedPassword', false)
            ->joinWith('user', false)
            ->joinWith('event', false)
            ->where([
                self::tableName() . '.events_id' => $eventId,
                Events::tableName() . '.statuses_id' => [
                    Statuses::getStatusId(Statuses::CONFIGURING),
                    Statuses::getStatusId(Statuses::READY),
                ], 
                Users::tableName() . '.statuses_id' => [
                    Statuses::getStatusId(Statuses::CONFIGURING),
                    Statuses::getStatusId(Statuses::READY),
                ]    
            ])
            ->asArray()
        ;

        if ($withDirectories) {
            $query->joinWith('modules');
        }

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'totalCount' => $query->distinct()->count(),
            'pagination' => [
                'pageSize' => $records,
                'route' => $route,
            ],
        ]);
    
        $models = $dataProvider->getModels();
        foreach ($models as &$model) {
            $model['password'] = EncryptedPasswords::decryptByPassword($model['password']);
            if ($withDirectories && !empty($model['modules'])) {
                $model['modules'] = array_map(function ($module) use ($model) {
                    $module['title'] = ModuleService::getTitleDirectoryModule($model['dir_prefix'], $module['number']);
                    $module['domain'] = VirtualHostService::getDomain(ModuleService::getTitleDirectoryModule($model['dir_prefix'], $module['number']));
                    return $module;
                }, $model['modules']);
            }
        }
        unset($model);
        $dataProvider->setModels($models);
    
        return $dataProvider;
    }

    public static function getExportStudents(?int $eventId = null): ?array
    {
        if (is_null($eventId)) {
            return null;
        }

        $students = self::find()
            ->select([
                'students_id',
                'fullName' => 'CONCAT(surname, " ", name, COALESCE(CONCAT(" ", patronymic), ""))',
                'login',
                'password' => EncryptedPasswords::tableName() . '.encrypted_password',
            ])
            ->where(['events_id' => $eventId])
            ->joinWith('encryptedPassword', false)
            ->joinWith('user', false)
            ->asArray()
            ->all()
        ;

        foreach ($students as &$student) {
            $student['password'] = EncryptedPasswords::decryptByPassword($student['password']);
        }
        unset($student);

        return $students;
    }
}
