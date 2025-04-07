<?php

namespace common\models;

use app\components\FileComponent;
use app\controllers\StudentController;
use common\services\ModuleService;
use Exception;
use Yii;
use yii\data\ActiveDataProvider;
use yii\helpers\VarDumper;
use yii\validators\FileValidator;

/**
 * This is the model class for table "{{%files}}".
 *
 * @property int $id
 * @property int $events_id
 * @property int|null $modules_id
 * @property string $name
 * @property string $extension
 *
 * @property Events $event
 * @property Modules $module
 */
class Files extends \yii\db\ActiveRecord
{
    const SCENARIO_UPLOAD_FILE = "upload-file";
    const PUBLIC_DIR = 'public';

    public array $files = [];

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%files}}';
    }

    public function scenarios()
    {
        $scenarios = parent::scenarios();
        $scenarios[self::SCENARIO_UPLOAD_FILE] = ['events_id', 'modules_id', 'files'];
        return $scenarios;
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['name', 'extension'], 'required'],
            [['events_id'], 'required', 'message' => 'Необхожимо выбрать чемпионат.'],
            [['events_id', 'modules_id'], 'integer'],
            [['name', 'extension'], 'string', 'max' => 255],
            [['events_id'], 'exist', 'skipOnError' => true, 'targetClass' => Events::class, 'targetAttribute' => ['events_id' => 'id']],
            [['modules_id'], 'exist', 'skipOnError' => true, 'targetClass' => Modules::class, 'targetAttribute' => ['modules_id' => 'id'], 'when' => function ($model) {
                return $model->modules_id !== '0';
            }],
            
            [['modules_id'], 'required', 'message' => 'Необхожимо выбрать расположение фалов.', 'on' => self::SCENARIO_UPLOAD_FILE],
            [['files'], 'required', 'on' => self::SCENARIO_UPLOAD_FILE],
            [['files'], 'file', 'maxFiles' => 0, 'maxSize' => Yii::$app->fileComponent->getMaxSizeFiles(), 'checkExtensionByMimeType' => true, 'on' => self::SCENARIO_UPLOAD_FILE],
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
            'modules_id' => 'Расположение',
            'name' => 'Название',
            'extension' => 'Расширение',
            'files' => 'Файлы',
        ];
    }

    /**
     * Gets query for [[Competencies]].
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
    public function getModule()
    {
        return $this->hasOne(Modules::class, ['id' => 'modules_id']);
    }

    public static function getDirectories(int|string|null $eventId = null): array
    {
        $directories = [
            0 => ucfirst(self::PUBLIC_DIR),
        ];

        if (!$eventId) {
            return $directories;
        }

        $modules = Modules::find()
            ->select(['id', 'number'])
            ->where(['events_id' => $eventId])
            ->asArray()
            ->all()
        ;

        foreach ($modules as $module) {
            $directories[$module['id']] = sprintf('Модуль %s', $module['number']);
        }

        return $directories;
    }

    /**
     * Get DataProvider files
     * 
     * @param int $records number of records.
     * 
     * @return ActiveDataProvider
     */
    public static function getDataProviderFiles(?int $eventId = null, int $records = 10): ActiveDataProvider
    {
        $query = self::find()
            ->select([
                self::tableName() . '.id',
                'module' => 'number',
                'name',
                'extension',
                'dir_title',
            ])
            ->where([self::tableName() . '.events_id' => $eventId])
            ->joinWith('event', false)
            ->joinWith('module', false)
            ->asArray()
        ;

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => $records,
                'route' => 'file',
            ],
        ]);

        $models = $dataProvider->getModels();
        foreach ($models as &$model) {
            $model['path'] = ($model['module'] ? ModuleService::getDirectoryModuleFileTitle($model['module']) . '/' : '') . "{$model['name']}.{$model['extension']}";
            $model['module'] = (is_null($model['module']) ? ucfirst(self::PUBLIC_DIR) : "Модуль {$model['module']}");
        }
        unset($model);
        $dataProvider->setModels($models);

        return $dataProvider;
    }

    /**
     * Finds a file by its name and directory.
     * 
     * @param string $filename file name.
     * @param string $event the name of the event directory.
     * 
     * @return array|null if the file is found, it returns the file data as an `array`, otherwise it returns `null`.
     */
    public static function findFile(int $event, string $filename): array|null
    {
        return self::find()
            ->select([
                'filename' => 'CONCAT(name, ".", extension)',
                'extension',
                'modules_id',
                'number',
            ])
            ->where([self::tableName() . '.events_id' => $event, 'filename' => $filename])
            ->joinWith('module', false)
            ->asArray()
            ->one()
        ;
    }
}
