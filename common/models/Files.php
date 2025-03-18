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
 * @property string $save_name
 * @property string $origin_name
 * @property string $extension
 *
 * @property Events $event
 * @property Modules $module
 */
class Files extends \yii\db\ActiveRecord
{
    public array $files = [];
    public object $file;
    public int|null $eventId = null;
    public string $expertPath = '';
    public array $studentPaths = [];

    const SCENARIO_UPLOAD_FILE = "upload-file";
    const SCENARIO_VALIDATE_FILE = "validate-file";

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

        $event = Events::findOne(['experts_id' => Yii::$app->user->id]);
        $eventPath = Yii::getAlias("@events/$event->dir_title");

        return $this->deleteFilesStudents($event->id) && Yii::$app->fileComponent->deleteFile("$eventPath/$this->save_name.$this->extension");
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
            [['save_name', 'origin_name', 'extension'], 'required'],
            [['events_id'], 'required', 'message' => 'Необхожимо выбрать чемпионат.'],
            [['events_id', 'modules_id'], 'integer'],
            [['save_name', 'origin_name', 'extension'], 'string', 'max' => 255],
            [['events_id'], 'exist', 'skipOnError' => true, 'targetClass' => Events::class, 'targetAttribute' => ['events_id' => 'id']],
            [['modules_id'], 'exist', 'skipOnError' => true, 'targetClass' => Modules::class, 'targetAttribute' => ['modules_id' => 'id'], 'when' => function ($model) {
                return $model->modules_id != '0';
            }],

            [['file'], 'file', 'maxSize' => Yii::$app->fileComponent->getMaxSizeFiles(), 'skipOnError' => false, 'on' => self::SCENARIO_VALIDATE_FILE],

            [['modules_id'], 'required', 'message' => 'Необхожимо выбрать расположение фалов.', /* 'on' => self::SCENARIO_UPLOAD_FILE */],
            [['files'], 'required', 'on' => self::SCENARIO_UPLOAD_FILE],
            [['files'], 'file', 'maxFiles' => 0, 'maxSize' => Yii::$app->fileComponent->getMaxSizeFiles(), 'on' => self::SCENARIO_UPLOAD_FILE],
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
            'save_name' => 'Сохраненное имя',
            'origin_name' => 'Оригинальное имя',
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
        if ($this->save_name) {
            $filePath = "$this->expertPath/$this->save_name.$file->extension";
            Yii::$app->fileComponent->deleteFile($filePath);

            foreach ($this->studentPaths as $studentPath) {
                $studentPath = Yii::getAlias($studentPath['alias']);
                Yii::$app->fileComponent->deleteFile("$studentPath/$this->save_name.$file->extension");
            }
        }
    }

    public static function getDirectories(?int $eventID = null): array
    {
        $directories = [
            0 => 'Public',
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

        foreach ($this->studentPaths as $studentPath) {
            $studentPath = Yii::getAlias($studentPath['alias']);

            if (!is_dir($studentPath)) {
                Yii::$app->fileComponent->createDirectory($studentPath);
            }

            if (!copy($filePath, "$studentPath/$filename")) {
                $errors[] = 'Не удалось скопировать файл.';
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
        $model->origin_name = $file->baseName;
        $model->extension = $file->extension;
        $model->save_name = $this->save_name;

        return $model->save();
    }

    public function validateFile($file)
    {
        $validator = new Files(['scenario' => self::SCENARIO_VALIDATE_FILE, 'file' => $file]);

        return [
            'isValid' => $validator->validate(),
            'errors' => $validator->getErrors('file')
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

        $this->save_name = '';

        $fileValidate = $this->validateFile($file);

        if (!$fileValidate['isValid']) {
            $fileInfo['errors'] = $fileValidate['errors'];
            return $fileInfo;
        }

        $this->save_name = Yii::$app->security->generateRandomString() . '_' . time();
        $filePath = "$this->expertPath/$this->save_name.$file->extension";
        if (!$file->saveAs($filePath)) {
            $fileInfo['errors'][] = "Не удалось сохранить файл $file->name.";
            $this->save_name = '';
            return $fileInfo;
        }

        if (!$this->saveFileEventToDb($file)) {
            $fileInfo['errors'][] = "Не удалось сохранить запись файла $file->name в базе данных.";
            Yii::$app->fileComponent->deleteFile($filePath);
            $this->save_name = '';
            return $fileInfo;
        }

        $copyErrors = $this->copyFileToStudents($filePath, "$this->save_name.$file->extension");
        if (!empty($copyErrors)) {
            $fileInfo['errors'] = array_merge($fileInfo['errors'], $copyErrors);
            $this->save_name = '';
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
    public function processFiles(): array|bool
    {
        $this->validate();

        if (!$this->hasErrors()) {
            $this->modules_id = ($this->modules_id == '0' ? null : $this->modules_id);
            $this->expertPath = Yii::getAlias("@events/" . $this->event->dir_title);
            $this->studentPaths = Students::find()
                ->select([
                    'CONCAT("@students/", login, "/public") as alias',
                ])
                ->where(['events_id' => $this->events_id])
                ->joinWith('user', false)
                ->asArray()
                ->all()
            ;
    
            foreach ($this->files as $file) {
                $transaction = Yii::$app->db->beginTransaction();
    
                try {
                    $fileInfo = $this->processFile($file);
    
                    if (!empty($fileInfo['errors'])) {
                        $this->addError('files', ['filename' => $file->name, 'errors' => $fileInfo['errors']]);
                        $transaction->rollBack();
                        $this->deleteFailedFile($file);
                        continue;
                    }
    
                    $transaction->commit();
                } catch (\Exception $e) {
                    $transaction->rollBack();
                    $this->addError('files', $e->getMessage());
                    // $result[] = ['filename' => $file->name, 'errors' => [$e->getMessage()]];
                    $this->deleteFailedFile($file);
                } catch (\Throwable $e) {
                    $transaction->rollBack();
                    $this->addError('files', $e->getMessage());
                    // $result[] = ['filename' => $file->name, 'errors' => [$e->getMessage()]];
                    $this->deleteFailedFile($file);
                }
            }
    
            return true;
        }

        return false;
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
                'origin_name',
                'save_name',
                'extension',
                'dir_title'
            ])
            ->where(['events_id' => $eventID])
            ->joinWith('event', false)
            ->asArray()
        ;

        return new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => $records,
                'route' => 'files',
            ],
        ]);
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
            if ($module = self::findOne(['id' => $id])) {
                if ($module->delete()) {
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
            $studentFile = Yii::getAlias('@students/' . $student->user->login . "/public/$this->save_name.$this->extension");
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
    public static function findFile(?string $filename = null, ?string $event = null): array|null
    {
        return self::find()
            ->select([
                "CONCAT(origin_name, '.', extension) AS originName",
                "type",
                "extension",
            ])
            ->where(['save_name' => $filename, 'dir_title' => $event])
            ->joinWith('event', false)
            ->asArray()
            ->one()
            ;
    }
}
