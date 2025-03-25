<?php

namespace common\models;

use app\components\FileComponent;
use app\controllers\StudentController;
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
    const SCENARIO_VALIDATE_FILE = "validate-file";
    const PUBLIC_DIR = 'public';

    public array $files = [];
    public object $file;
    public int|null $eventId = null;
    public string $expertPath = '';
    public array $studentPaths = [];

    public function scenarios()
    {
        $scenarios = parent::scenarios();
        $scenarios[self::SCENARIO_UPLOAD_FILE] = ['events_id', 'modules_id', 'files'];
        $scenarios[self::SCENARIO_VALIDATE_FILE] = ['file'];
        return $scenarios;
    }

    public function beforeDelete()
    {
        if (!parent::beforeDelete()) {
            return false;
        }

        $eventPath = Yii::getAlias("@events/" . $this->event->dir_title);

        return $this->deleteFilesStudents($this->event->id) && Yii::$app->fileComponent->deleteFile($eventPath . (is_null($this->modules_id) ? '' : '/' . Events::getDirectoryModuleFileTitle($this->module?->number)) .  "/{$this->name}.{$this->extension}");
    }

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%files}}';
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
                return $model->modules_id != '0';
            }],

            [['file'], 'file', 'maxSize' => Yii::$app->fileComponent->getMaxSizeFiles(), 'skipOnError' => false, 'on' => self::SCENARIO_VALIDATE_FILE],

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

    public function deleteFailedFile($file)
    {
        if ($this->name) {
            $filePath = "$this->expertPath/$this->name.$file->extension";
            Yii::$app->fileComponent->deleteFile($filePath);

            foreach ($this->studentPaths as $studentPath) {
                $studentPath = Yii::getAlias($studentPath['alias']);
                Yii::$app->fileComponent->deleteFile("$studentPath/$this->name.$file->extension");
            }
        }
    }

    public static function getDirectories(int|string|null $eventID = null): array
    {
        $directories = [
            0 => ucfirst(self::PUBLIC_DIR),
        ];

        if ($eventID === null) {
            return $directories;
        }

        $modules = Modules::find()
            ->select(['id', 'number'])
            ->where(['events_id' => $eventID])
            ->asArray()
            ->all()
        ;

        foreach ($modules as $module) {
            $directories[$module['id']] = sprintf('Модуль %s', $module['number']);
        }

        return $directories;
    }

    public function getFilename(string $filename, string $extension): string
    {
        $filePath = Yii::getAlias("@events/{$this->event->dir_title}" . (is_null($this->modules_id) ? '' : '/' . Events::getDirectoryModuleFileTitle($this->module?->number)) .  "/{$filename}.{$extension}");
        $counter = 1;

        while (file_exists($filePath)) {
            $counter += 1;
            $filePath = Yii::getAlias('@events/' . $this->event->dir_title . (is_null($this->modules_id) ? '' : '/' . Events::getDirectoryModuleFileTitle($this->module?->number)) . "/{$filename} ({$counter}).{$extension}");
        }

        return $filename . ($counter > 1 ? " ({$counter})" : '');
    }

    /**
     * Copies files to students.
     * 
     * @param string $compDir the directory where the file is copied from.
     * @param array $filename file name.
     * @param array $students an array with student data.
     */
    public function copyFileToStudents(string $filePath, string $filename): array
    {
        $errors = [];
        $destinations = [];

        foreach ($this->studentPaths as $studentPath) {
            $destPath = Yii::getAlias($studentPath['alias'] . (is_null($this->modules_id) ? '' : '/' . Students::getDirectoryModuleFileTitle($this->module?->number)));
            $destinations[] = "$destPath/$filename";
            // if (!copy($filePath, "$destPath/$filename")) {
            //     $errors[] = "Не удалось скопировать файл.";
            //     break;
            // }
        }

        foreach ($destinations as $dest) {
            if (!copy($filePath, $dest)) {
                $errors[] = "Не удалось скопировать файл в $dest.";
                break;
            }
        }

        return $errors;
    }

    /**
     * Add file competence
     * 
     * @param string $eventId ID event.
     * @param string $baseName the name of the uploaded file.
     * @param string $extension the extension of the downloaded file.
     * @param string $type the type of the downloaded file.
     * 
     * @return Files
     */
    public function saveFileEventToDb(yii\web\UploadedFile $file): bool
    {
        $model = new Files();
        $model->events_id = $this->events_id;
        $model->modules_id = $this->modules_id;
        $model->extension = $file->extension;
        $model->name = $this->name;

        return $model->save();
    }

    public function validateFile($file)
    {
        $validator = new FileValidator([
            'maxSize' => Yii::$app->fileComponent->getMaxSizeFiles(),
            'skipOnEmpty' => false,
        ]);
        $isValid = $validator->validate($file, $error);
    
        return [
            'isValid' => $isValid,
            'errors' => $error ? [$error] : []
        ];
    }

    /**
     * Saves the file
     * 
     * @param int $eventId ID event.
     * @param string $dir the file's save directory.
     * @param array $students an array of students who need to copy the saved file.
     * @param yii\web\UploadedFile $file the file to save.
     * 
     * @throws Exception|Throwable throws an exception if an error occurs when uploading files.
     */
    public function processFile(yii\web\UploadedFile $file)
    {
        $fileInfo = [
            'filename' => $file->name,
            'errors' => []
        ];

        $this->name = '';

        $fileValidate = $this->validateFile($file);

        if (!$fileValidate['isValid']) {
            $fileInfo['errors'] = $fileValidate['errors'];
            return $fileInfo;
        }

        $this->name = $this->getFilename($file->baseName, $file->extension);
        $filePath = Yii::getAlias("@events/{$this->event->dir_title}" . (is_null($this->modules_id) ? '' : '/' . Events::getDirectoryModuleFileTitle($this->module?->number)) . "/{$this->name}.{$file->extension}");
        if (!$file->saveAs($filePath)) {
            $fileInfo['errors'][] = "Не удалось сохранить файл $file->name.";
            $this->name = '';
            return $fileInfo;
        }

        if (!$this->saveFileEventToDb($file)) {
            $fileInfo['errors'][] = "Не удалось сохранить запись файла $file->name в базе данных.";
            Yii::$app->fileComponent->deleteFile($filePath);
            $this->name = '';
            return $fileInfo;
        }

        $copyErrors = $this->copyFileToStudents($filePath, "{$this->name}.{$file->extension}");
        if (!empty($copyErrors)) {
            $fileInfo['errors'] = array_merge($fileInfo['errors'], $copyErrors);
            $this->name = '';
            return $fileInfo;
        }

        return $fileInfo;
    }

    /**
     * Uploads files
     * 
     * @return array returns the value `true` if the files were uploaded successfully.
     * 
     * @throws Exception|Throwable throws an exception if an error occurs when uploading files.
     */
    public function processFiles(): bool
    {
        $this->validate();
        if ($this->hasErrors()) {
            return false;
        }

        $this->modules_id = ($this->modules_id == '0' ? null : $this->modules_id);
        $this->expertPath = Yii::getAlias("@events/" . $this->event->dir_title);
        $this->studentPaths = Students::find()
            ->select(['CONCAT("@students/", login, "/'.self::PUBLIC_DIR.'") as alias'])
            ->where(['events_id' => $this->events_id])
            ->joinWith('user', false)
            ->asArray()
            ->all();

        $allSuccess = true;
        foreach ($this->files as $file) {
            $transaction = Yii::$app->db->beginTransaction();
            try {
                $fileInfo = $this->processFile($file);
                if (!empty($fileInfo['errors'])) {
                    $this->addError('files', ['filename' => $file->name, 'errors' => $fileInfo['errors']]);
                    $allSuccess = false;
                    $transaction->rollBack();
                    $this->deleteFailedFile($file);
                } else {
                    $transaction->commit();
                }
            } catch (\Exception $e) {
                $transaction->rollBack();
                $this->addError('files', ['filename' => $file->name, 'errors' => [$e->getMessage()]]);
                $this->deleteFailedFile($file);
                $allSuccess = false;
            }
        }
    
        return $allSuccess;
    }

    /**
     * Get DataProvider files
     * 
     * @param int $records number of records.
     * 
     * @return ActiveDataProvider
     */
    public static function getDataProviderFiles(?int $eventID = null, int $records = 10): ActiveDataProvider
    {
        $query = self::find()
            ->select([
                self::tableName() . '.id',
                'number as module',
                'name',
                'extension',
                'dir_title',
            ])
            ->where([self::tableName() . '.events_id' => $eventID])
            ->joinWith('event', false)
            ->joinWith('module', false)
            ->asArray()
        ;

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => $records,
                'route' => 'files',
            ],
        ]);

        $models = $dataProvider->getModels();
        foreach ($models as &$model) {
            $model['path'] = (is_null($model['module']) ? '' : Events::getDirectoryModuleFileTitle($model['module']) . '/') . "{$model['name']}.{$model['extension']}";
            $model['module'] = (is_null($model['module']) ? ucfirst(self::PUBLIC_DIR) : 'Модуль ' . $model['module']);
        }
        unset($model);
        $dataProvider->setModels($models);

        return $dataProvider;
    }

    /**
     * Deletes the file.
     * 
     * @param string|null $id student ID.
     * 
     * @return bool returns the value `true` if the file was successfully deleted.
     */
    public static function deleteFileEvent(string|null $id)
    {
        $transaction = Yii::$app->db->beginTransaction();   
        try {
            if ($model = self::findOne(['id' => $id])) {
                if ($model->delete()) {
                    $transaction->commit();
                    return true;
                }

                $transaction->rollBack();
            }
            return false;
        } catch(\Exception $e) {
            $transaction->rollBack();
            var_dump($e);die;
        } catch(\Throwable $e) {
            $transaction->rollBack();
        }

        return false;
    }

    public static function deleteFilesEvent(array $modulesId): bool
    {
        foreach ($modulesId as $moduleId) {
            if (!self::deleteFileEvent($moduleId)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Deletes a file from students.
     * 
     * @return bool `true` on success or `false` on failure.
     */
    public function deleteFilesStudents(int $eventId): bool
    {
        $students = Students::findAll(['events_id' => $eventId]);

        foreach ($students as $student) {
            $studentFile = Yii::getAlias('@students/' . $student->user->login . '/public/' . (is_null($this->modules_id) ? '' : Students::getDirectoryModuleFileTitle($this->module?->number) . '/') . "$this->name.$this->extension");
            if (!Yii::$app->fileComponent->deleteFile($studentFile)) {
                return false; 
            }
        }

        return true;
    }

    /**
     * Finds a file by its name and directory.
     * 
     * @param string $filename file name.
     * @param string $event the name of the event directory.
     * 
     * @return array|null if the file is found, it returns the file data as an `array`, otherwise it returns `null`.
     */
    public static function findFile(string $event, string $filename): array|null
    {
        return self::find()
            ->select([
                "CONCAT(name, '.', extension) AS filename",
                'extension',
                'modules_id',
                'number',
            ])
            ->where([self::tableName() . '.events_id' => $event, 'name' => $filename])
            ->joinWith('module', false)
            ->asArray()
            ->one()
            ;
    }
}
