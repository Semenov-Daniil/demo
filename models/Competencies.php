<?php

namespace app\models;

use app\components\AppComponent;
use app\components\DbComponent;
use app\components\FileComponent;
use Yii;
use yii\db\ActiveRecord;
use yii\helpers\VarDumper;

/**
 * This is the model class for table "dm_competencies".
 *
 * @property int $experts_id
 * @property string $title
 * @property string $dir_title
 *
 * @property Users $users
 * @property Modules[] $modules
 * @property StudentsCompetencies[] $studentsCompetencies
 */
class Competencies extends ActiveRecord
{
    public int $module_count = 1;

    public function scenarios()
    {
        $scenarios = parent::scenarios();
        $scenarios[self::SCENARIO_DEFAULT] = ['title', 'module_count', '!experts_id', '!dir_title'];
        return $scenarios;
    }

    public function beforeSave($insert)
    {
        if (parent::beforeSave($insert)) {
            if ($this->isNewRecord) {
                $this->dir_title = $this->getUniqueStr('dir_title', 8, ['lowercase']);

                return FileComponent::createDirectory(Yii::getAlias('@competencies') . '/' . $this->dir_title);
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
            for ($i = 0; $i < $this->module_count; $i++) {
                $module = new Modules(['competencies_id' => $this->experts_id]);
                $module->save();
            }
        }
    }

    public function beforeDelete()
    {
        if (!parent::beforeDelete()) {
            return false;
        }

        FileComponent::removeDirectory(Yii::getAlias('@competencies') . '/' . $this->dir_title);

        return true;
    }
    
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%competencies}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['title', 'module_count'], 'required'],
            [['module_count'], 'integer', 'min' => 1],
            [['experts_id'], 'integer'],
            [['title', 'dir_title'], 'string', 'max' => 255],
            [['experts_id'], 'exist', 'skipOnError' => true, 'targetClass' => Users::class, 'targetAttribute' => ['experts_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'experts_id' => 'Эксперт',
            'title' => 'Название тестирования',
            'module_count' => 'Кол-во модулей',
            'dir_title' => 'Название директории'
        ];
    }

    public function attributes() {
        return [
            ...parent::attributes(),
            'module_count',
        ];
    }

    /**
     * Gets query for [[Users]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getUsers()
    {
        return $this->hasOne(Users::class, ['id' => 'experts_id'])->inverseOf('competencies');
    }

    /**
     * Gets query for [[Modules]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getModules()
    {
        return $this->hasMany(Modules::class, ['competencies_id' => 'experts_id'])->inverseOf('competencies');
    }

    /**
     * Gets query for [[StudentsCompetencies]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getStudentsCompetencies()
    {
        return $this->hasMany(StudentsCompetencies::class, ['competencies_id' => 'experts_id'])->inverseOf('competencies');
    }

    /**
     * Gets query for [[FilesCompetencies]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getFilesCompetencies()
    {
        return $this->hasMany(FilesCompetencies::class, ['competencies_id' => 'experts_id'])->inverseOf('competencies');
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
        $str = $charSets ? AppComponent::generateRandomString($length, $charSets) : Yii::$app->security->generateRandomString($length);
    
        while(!DbComponent::isUniqueValue(self::class, $attr, $str)) {
            $str = $charSets ? AppComponent::generateRandomString($length, $charSets) : Yii::$app->security->generateRandomString($length);
        }

        return $str;
    }
}
