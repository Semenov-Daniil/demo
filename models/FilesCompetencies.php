<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "dm_files_competencies".
 *
 * @property int $id
 * @property int $competencies_id
 * @property string $title
 * @property string $filename
 * @property string $extension
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
            [['competencies_id', 'title', 'filename', 'extension'], 'required'],
            [['competencies_id'], 'integer'],
            [['title', 'filename', 'extension'], 'string', 'max' => 255],
            [['competencies_id'], 'exist', 'skipOnError' => true, 'targetClass' => Competencies::class, 'targetAttribute' => ['competencies_id' => 'experts_id']],
            [['files'], 'file', 'skipOnEmpty' => false, 'maxFiles' => 0]
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

    public function uploadFiles()
    {
        if ($this->validate()) {
            $transaction = Yii::$app->db->beginTransaction();
            try {
                $dir = Yii::getAlias('@competencies') . '/' . Competencies::findOne(['experts_id' => Yii::$app->user->id])?->dir_title;
                foreach ($this->files as $file) {
                    $model = $this->addFileCompetence($file->baseName, $file->extension);
                    if ($model->hasErrors()) {
                        $transaction->rollBack();
                        return false;
                    }
                    $file->saveAs("$dir/$model->title.$model->extension");
                }
                $transaction->commit();
                return true;
            } catch(\Exception $e) {
                $transaction->rollBack();
            } catch(\Throwable $e) {
                $transaction->rollBack();
            } 
        }

        return false;
    }

    /**
     * Add file competence
     * 
     * @param string $baseName the name of the uploaded file.
     * @param string $extension the extension of the downloaded file.
     * 
     * @return FilesCompetencies
     */
    public function addFileCompetence(string $baseName, string $extension): FilesCompetencies
    {
        $model = new FilesCompetencies();
        $model->competencies_id = Yii::$app->user->id;
        $model->title = Yii::$app->security->generateRandomString();
        $model->filename = $baseName;
        $model->extension = $extension;
        $model->save();
        return $model;
    }
}
