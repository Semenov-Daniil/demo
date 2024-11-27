<?php

namespace app\models;

use app\components\AppComponent;
use app\components\DbComponent;
use app\components\FileComponent;
use Yii;
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
 * @property Users $users
 * @property Modules[] $modules
 * @property StudentsEvents[] $students
 * @property FilesEvents[] $files
 */
class Events extends ActiveRecord
{
    public int $countModules = 1;

    public function scenarios()
    {
        $scenarios = parent::scenarios();
        $scenarios[self::SCENARIO_DEFAULT] = ['title', 'countModules', '!experts_id', '!dir_title'];
        return $scenarios;
    }

    public function beforeSave($insert)
    {
        if (parent::beforeSave($insert)) {
            if ($this->isNewRecord) {
                $this->dir_title = $this->getUniqueStr('dir_title', 8, ['lowercase']);

                return FileComponent::createDirectory(Yii::getAlias('@events') . '/' . $this->dir_title);
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
            }
        }
    }

    public function beforeDelete()
    {
        if (!parent::beforeDelete()) {
            return false;
        }

        FileComponent::removeDirectory(Yii::getAlias('@events') . '/' . $this->dir_title);

        return true;
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
    public function getUsers()
    {
        return $this->hasOne(Users::class, ['id' => 'experts_id'])->inverseOf('events');
    }

    /**
     * Gets query for [[Modules]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getModules()
    {
        return $this->hasMany(Modules::class, ['events_id' => 'id'])->inverseOf('events');
    }

    /**
     * Gets query for [[StudentsEvents]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getStudents()
    {
        return $this->hasMany(StudentsEvents::class, ['events_id' => 'id'])->inverseOf('events');
    }

    /**
     * Gets query for [[Filesevents]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getFiles()
    {
        return $this->hasMany(FilesEvents::class, ['events_id' => 'id'])->inverseOf('events');
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
