<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "dm_students_competencies".
 *
 * @property int $students_id
 * @property int $competencies_id
 * @property string $dir_title
 *
 * @property Competencies $competencies
 * @property Users $students
 */
class StudentsCompetencies extends \yii\db\ActiveRecord
{
    public function beforeSave($insert)
    {
        if (parent::beforeSave($insert)) {
            if ($this->isNewRecord) {
                if ($this->addDirStudent()) {
                    return $this->addDbStudent();
                }
                
                Yii::$app->generationFile->deleteDir($this->dir_title);
                return false;
            }
            return true;
        } else {
            return false;
        }
    }

    public function beforeDelete()
    {
        if (!parent::beforeDelete()) {
            return false;
        }

        Yii::$app->generationFile->deleteDir($this->dir_title);
        return $this->deleteDbStudent();
    }

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'dm_students_competencies';
    }

    public function scenarios()
    {
        $scenarios = parent::scenarios();
        $scenarios[self::SCENARIO_DEFAULT] = ['!students_id', '!competencies_id'];
        return $scenarios;
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['students_id', 'competencies_id'], 'required'],
            [['students_id', 'competencies_id'], 'integer'],
            [['competencies_id'], 'exist', 'skipOnError' => true, 'targetClass' => Competencies::class, 'targetAttribute' => ['competencies_id' => 'users_id']],
            [['students_id'], 'exist', 'skipOnError' => true, 'targetClass' => Users::class, 'targetAttribute' => ['students_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'students_id' => 'Students ID',
            'competencies_id' => 'Competencies ID',
        ];
    }

    /**
     * Gets query for [[Competencies]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getCompetencies()
    {
        return $this->hasOne(Competencies::class, ['users_id' => 'competencies_id']);
    }

    /**
     * Gets query for [[Users]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getUsers()
    {
        return $this->hasOne(Users::class, ['id' => 'students_id']);
    }

    /**
     * Gets query for [[Passwords]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getPasswords(): object
    {
        return $this->hasOne(Passwords::class, ['users_id' => 'students_id']);
    }

    public function addDirStudent()
    {
        if ($this->dir_title = Yii::$app->generationFile->createDir()) {
            $num_modeles = Competencies::findOne(['users_id' => $this->competencies_id])?->num_modules;
            for($i = 0; $i < $num_modeles; $i++) {
                if (!Yii::$app->generationFile->createDir("$this->dir_title/$this->dir_title-m" . ($i + 1))) {
                    return false;
                }
            }
            return true;
        }
        return false;
    }

    public function addDbStudent()
    {
        $login = Users::findOne(['id' => $this->students_id])?->login;
        $password = Passwords::findOne(['users_id'=>$this->students_id])?->password;
        if (Yii::$app->generationDb->createUser($login, $password)) {
            $num_modeles = Competencies::findOne(['users_id' => $this->competencies_id])?->num_modules;
            for($i = 0; $i < $num_modeles; $i++) {
                if (!Yii::$app->generationDb->createDb($login . '_m' . ($i + 1)) && !Yii::$app->generationDb->addRuleDb($login, $login . '_m' . ($i + 1))) {
                    return false;
                }
            }

            return true;
        }

        return false;
    }

    public function deleteDbStudent()
    {
        $login = Users::findOne(['id' => $this->students_id])->login;
        if (Yii::$app->generationDb->deleteUser($login)) {
            $num_modeles = Competencies::findOne(['users_id' => $this->competencies_id])?->num_modules;
            for($i = 0; $i < $num_modeles; $i++) {
                if (!Yii::$app->generationDb->deleteDb($login . '_m' . ($i + 1))) {
                    return false;
                }
            }
            return true;
        }

        return false;
    }
}
