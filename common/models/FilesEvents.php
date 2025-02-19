<?php

namespace common\models;

use app\components\FileComponent;
use app\controllers\StudentController;
use Exception;
use Yii;
use yii\data\ActiveDataProvider;
use yii\helpers\VarDumper;

/**
 * This is the model class for table "dm_files_events".
 *
 * @property int $id
 * @property int $events_id
 * @property string $save_name
 * @property string $origin_name
 * @property string $extension
 * @property string $type
 *
 * @property Events $event
 */
class FilesEvents extends \yii\db\ActiveRecord
{
    public array $files = [];
    public $file;
    public array $students = [];

    const SCENARIO_UPLOAD_FILE = "upload-file";

    public function scenarios()
    {
        $scenarios = parent::scenarios();
        $scenarios[self::SCENARIO_UPLOAD_FILE] = ['files'];
        return $scenarios;
    }

    public function beforeDelete()
    {
        if (!parent::beforeDelete()) {
            return false;
        }

        $event = Events::findOne(['experts_id' => Yii::$app->user->id]);
        $eventPath = Yii::getAlias('@events') . "/" . $event->dir_title;

        return $this->deleteFilesStudents($event->id) && Yii::$app->fileComponent->deleteFile("$eventPath/$this->save_name.$this->extension");
    }

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%files_events}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['events_id', 'save_name', 'origin_name', 'extension', 'type'], 'required'],
            [['events_id'], 'integer'],
            [['save_name', 'origin_name', 'extension', 'type'], 'string', 'max' => 255],
            [['events_id'], 'exist', 'skipOnError' => true, 'targetClass' => Events::class, 'targetAttribute' => ['events_id' => 'id']],
            [['files'], 'file', 'skipOnEmpty' => true, 'maxFiles' => 0, 'maxSize' => Yii::$app->fileComponent->getMaxSizeFiles(), 'on' => self::SCENARIO_UPLOAD_FILE],
            [['file'], 'file', 'maxSize' => Yii::$app->fileComponent->getMaxSizeFiles()],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'events_id' => 'Competencies ID',
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
     * Add file competence
     * 
     * @param string $eventId ID event.
     * @param string $baseName the name of the uploaded file.
     * @param string $extension the extension of the downloaded file.
     * @param string $type the type of the downloaded file.
     * 
     * @return FilesEvents
     */
    public function saveFileEvent(int $eventId, string $dir, yii\web\UploadedFile $file): FilesEvents
    {
        $model = new FilesEvents();

        $model->events_id = $eventId;
        $model->file = $file;
        $model->origin_name = $file->baseName;
        $model->extension = $file->extension;
        $model->type = $file->type;
        $model->save_name = Yii::$app->security->generateRandomString();
        
        // $model->validate();

        // if (!$model->hasErrors()) {
        //     $model->save();
        //     return $model;
        // }

        if ($model->save()) {
            if ($file->saveAs("$dir/$model->save_name.$model->extension")) {
                $this->copyFileStudents($dir, "$model->save_name.$model->extension");
            } else {
                $model->addError('file', 'Не удалось сохранить файл.');
            }
        }

        return $model;
    }

    /**
     * Copies files to students.
     * 
     * @param string $compDir the directory where the file is copied from.
     * @param array $filename file name.
     * @param array $students an array with student data.
     */
    public function copyFileStudents(string $compDir, string $filename): void
    {
        foreach ($this->students as $student) {
            $studentPath = Yii::getAlias('@students/' . $student['login'] . '/public');

            if (!is_dir($studentPath)) {
                Yii::$app->fileComponent->createDirectory($studentPath);
            }

            if (!copy("$compDir/$filename", "$studentPath/$filename")) {
                throw new Exception("Failed to copy file from $compDir/$filename to $studentPath/$filename");
            }
        }
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
    public function saveFile(int $eventId, string $dir, yii\web\UploadedFile $file): array|bool
    {
        $transaction = Yii::$app->db->beginTransaction();
        $model = null;
        try {
            $model = $this->saveFileEvent($eventId, $dir, $file);

            if ($model->hasErrors()) {
                $transaction->rollBack();
                return $model->getErrors();
            }

            $transaction->commit();
            return true;
        } catch(\Exception $e) {
            if ($model) Yii::$app->fileComponent->deleteFile("$dir/$model->save_name.$model->extension");
            $transaction->rollBack();
        } catch(\Throwable $e) {
            if ($model) Yii::$app->fileComponent->deleteFile("$dir/$model->save_name.$model->extension");
            $transaction->rollBack();
        } 

        return [
            'file' => [
                'Не удалось сохранить файл.'
            ]
        ];
    }

    /**
     * Uploads files
     * 
     * @return array returns the value `true` if the files were uploaded successfully.
     * 
     * @throws Exception|Throwable throws an exception if an error occurs when uploading files.
     */
    public function uploadFiles(): array
    {
        $answer = [];

        $event = Events::findOne(['experts_id' => Yii::$app->user->id]);
        $dir = Yii::getAlias("@events/$event->dir_title");

        $this->students = StudentsEvents::find()
            ->select([
                'login',
            ])
            ->where(['events_id' => $event?->id])
            ->joinWith('user', false)
            ->asArray()
            ->all()
        ;

        foreach ($this->files as $file) {
            $saveFile = $this->saveFile($event?->id, $dir, $file);

            if (is_array($saveFile)) {
                $answer[] = [
                    'filename' => $file->name,
                    'errors' => $saveFile['file']
                ];
            }

            // var_dump($answer);die;
            // if (!$this->saveFile($event?->id, $dir, $file)) {
            //     return false;
            // }
        }

        $this->students = [];

        return $answer;

        // if ($this->validate()) {
        // }

        // return ;
    }

    /**
     * Get DataProvider files
     * 
     * @param int $records number of records.
     * 
     * @return ActiveDataProvider
     */
    public static function getDataProviderFiles(int $records): ActiveDataProvider
    {
        $eventId = Yii::$app->user->identity->event?->id;

        $query = self::find()
            ->select([
                self::tableName() . '.id as fileId',
                'CONCAT(origin_name, \'.\', extension) AS originName',
                self::tableName() . '.save_name as saveName',
                'dir_title as dirTitle'
            ])
            ->where(['events_id' => $eventId])
            ->joinWith('event', false)
            ->orderBy([
                'fileId' => SORT_DESC
            ])
            ->asArray()
        ;

        return new ActiveDataProvider([
            'query' => $query,
            'key' => function ($model) {
                return $model['fileId'];
            },
            'pagination' => [
                'pageSize' => $records,
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
        if (!is_null($id)) {
            if ($model = self::findOne(['id' => $id])) {
                if ($model->delete()) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Deletes a file from students.
     * 
     * @return bool `true` on success or `false` on failure.
     */
    public function deleteFilesStudents(int $eventId): bool
    {
        $students = StudentsEvents::findAll(['events_id' => $eventId]);

        foreach ($students as $student) {
            $studentFile = Yii::getAlias('@students') . "/" . $student->user->login . "/public/" . "$this->save_name.$this->extension";
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
    public static function findFile(string $filename, string $event): array|null
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
