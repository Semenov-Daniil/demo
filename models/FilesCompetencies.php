<?php

namespace app\models;

use app\components\FileComponent;
use app\controllers\StudentController;
use Exception;
use Yii;
use yii\data\ActiveDataProvider;

/**
 * This is the model class for table "dm_files_competencies".
 *
 * @property int $id
 * @property int $competencies_id
 * @property string $title
 * @property string $filename
 * @property string $extension
 * @property string $type
 *
 * @property Competencies $competencies
 */
class FilesCompetencies extends \yii\db\ActiveRecord
{
    public array $files = [];

    const SCENARIO_UPLOAD_FILE = "upload-file";

    public function scenarios()
    {
        $scenarios = parent::scenarios();
        $scenarios[self::SCENARIO_UPLOAD_FILE] = ['files'];
        return $scenarios;
    }

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%files_competencies}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['competencies_id', 'title', 'filename', 'extension', 'type'], 'required'],
            [['competencies_id'], 'integer'],
            [['title', 'filename', 'extension', 'type'], 'string', 'max' => 255],
            [['competencies_id'], 'exist', 'skipOnError' => true, 'targetClass' => Competencies::class, 'targetAttribute' => ['competencies_id' => 'experts_id']],
            [['files'], 'file', 'skipOnEmpty' => false, 'maxFiles' => 0, 'maxSize' => FileComponent::getMaxSizeFiles()]
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'competencies_id' => 'Competencies ID',
            'title' => 'Title',
            'filename' => 'Название файла',
            'extension' => 'Расширение',
            'files' => 'Файлы',
        ];
    }

    /**
     * Gets query for [[Competencies]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getCompetencies()
    {
        return $this->hasOne(Competencies::class, ['experts_id' => 'competencies_id'])->inverseOf('files-competencies');
    }

    /**
     * Add file competence
     * 
     * @param string $baseName the name of the uploaded file.
     * @param string $extension the extension of the downloaded file.
     * @param string $type the type of the downloaded file.
     * 
     * @return FilesCompetencies
     */
    public function addFileCompetence(string $baseName, string $extension, string $type): FilesCompetencies
    {
        $model = new FilesCompetencies();
        $model->competencies_id = Yii::$app->user->id;
        $model->title = Yii::$app->security->generateRandomString();
        $model->filename = $baseName;
        $model->extension = $extension;
        $model->type = $type;
        $model->save();
        return $model;
    }

    public function copyFileStudent($compDir, $filename, $students)
    {
        foreach ($students as $student) {
            $studentPath = Yii::getAlias('@users') . "/" . $student['login'] . "/public";

            if (!is_dir($studentPath)) {
                FileComponent::createDirectory($studentPath);
            }

            if (!copy("$compDir/$filename", "$studentPath/$filename")) {
                throw new Exception("Failed to copy file from $compDir/$filename to $studentPath/$filename");
            }
        }
    }

    public function saveFile($dir, $students, $file)
    {
        $transaction = Yii::$app->db->beginTransaction();
        try {
            $model = $this->addFileCompetence($file->baseName, $file->extension, $file->type);
            if ($model->hasErrors()) {
                $transaction->rollBack();
            }

            if (!$file->saveAs("$dir/$model->title.$model->extension")) {
                throw new Exception("The file could not be saved $dir/$model->title.$model->extension");
            }

            $this->copyFileStudent($dir, "$model->title.$model->extension", $students);

            $transaction->commit();
        } catch(\Exception $e) {
            FileComponent::removeDirectory("$dir/$model->title.$model->extension");
            $transaction->rollBack();
            var_dump($e);die;
        } catch(\Throwable $e) {
            FileComponent::removeDirectory("$dir/$model->title.$model->extension");
            $transaction->rollBack();
            var_dump($e);die;
        } 
    }

    /**
     * Uploads files
     * 
     * @return bool returns the value `true` if the files were uploaded successfully.
     * 
     * @throws Exception|Throwable throws an exception if an error occurs when uploading files.
     */
    public function uploadFiles(): bool
    {
        if ($this->validate()) {
            $dir = Yii::getAlias('@competencies') . '/' . Competencies::findOne(['experts_id' => Yii::$app->user->id])?->dir_title;
            $students = StudentsCompetencies::find()
                ->select([
                    "students_id",
                    "login",
                ])
                ->where(['competencies_id' => Yii::$app->user->id])
                ->joinWith('users', false)
                ->asArray()
                ->all()
                ;

            foreach ($this->files as $file) {
                $this->saveFile($dir, $students, $file); 
            }
            return true;
        }

        return false;
    }

    /**
     * Get DataProvider files
     * 
     * @return ActiveDataProvider
     */
    public static function getDataProviderFiles(): ActiveDataProvider
    {
        return new ActiveDataProvider([
            'query' => self::find()
                ->select([
                    self::tableName() . '.id as fileId',
                    "CONCAT(filename, '.', extension) AS originFullName",
                    self::tableName() . ".title as saveName",
                    "dir_title as dirTitle"
                ])
                ->joinWith('competencies', false)
                ->asArray()
            ,
            'key' => function ($model) {
                return $model['fileId'];
            },
        ]);
    }

    /**
     * Deletes the file.
     * 
     * @param string|null $id student ID.
     * 
     * @return bool returns the value `true` if the file was successfully deleted.
     */
    public static function deleteFile(string|null $id)
    {
        if (!is_null($id)) {
            
        }
        return false;
    }
}
