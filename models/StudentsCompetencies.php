<?php

namespace app\models;

use app\components\DbComponent;
use app\components\FileComponent;
use Yii;
use yii\data\ActiveDataProvider;
use yii\db\ActiveRecord;

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
class StudentsCompetencies extends ActiveRecord
{
    public string $surname = '';
    public string $name = '';
    public string|null $middle_name = '';

    const SCENARIO_ADD_STUDENT = "add-student";
    const TITLE_ROLE_STUDENT = "student";

    public function beforeSave($insert)
    {
        if (parent::beforeSave($insert)) {
            if ($this->isNewRecord) {
                if ($this->addDbStudent()) {
                    return $this->addDirStudent();
                }
                
                return false;
            }
            return true;
        }
        return false;
    }

    public function beforeDelete()
    {
        if (!parent::beforeDelete()) {
            return false;
        }

        if ($this->deleteDbStudent()) {
            FileComponent::deleteDir($this->dir_title);
            return true;
        }
        
        return false;
    }

    public function scenarios()
    {
        $scenarios = parent::scenarios();
        $scenarios[self::SCENARIO_DEFAULT] = ['!students_id', '!competencies_id', '!dir_title'];
        $scenarios[self::SCENARIO_ADD_STUDENT] = ['surname', 'name', 'middle_name'];
        return $scenarios;
    }

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%students_competencies}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['surname', 'name', 'students_id', 'competencies_id'], 'required'],
            [['surname', 'name', 'middle_name', 'dir_title'], 'string', 'max' => 255],
            [['surname', 'name', 'middle_name', 'dir_title'], 'trim'],
            [['students_id', 'competencies_id'], 'integer'],
            ['middle_name', 'default', 'value' => null],
            [['competencies_id'], 'exist', 'skipOnError' => true, 'targetClass' => Competencies::class, 'targetAttribute' => ['competencies_id' => 'experts_id']],
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
            'competencies_id' => 'Компетенция',
            'dir_title' => 'Директория',
            'surname' => 'Фамилия',
            'name' => 'Имя',
            'middle_name' => 'Отчество',
        ];
    }

    public function attributes() {
        return [
            ...parent::attributes(),
            'surname',
            'name',
            'middle_name'
        ];
    }

    /**
     * Gets query for [[Competencies]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getCompetencies()
    {
        return $this->hasOne(Competencies::class, ['experts_id' => 'competencies_id']);
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

    /**
     * Get DataProvider students
     * 
     * @return array
     */
    public static function getDataProviderStudents($page)
    {
        return new ActiveDataProvider([
            'query' => StudentsCompetencies::find()
                ->select([
                    'students_id',
                    'surname',
                    'name',
                    'middle_name',
                    'login',
                    Passwords::tableName() . '.password',
                    'dir_title',
                ])
                ->where(['roles_id' => Roles::getRoleId(self::TITLE_ROLE_STUDENT), 'competencies_id' => Yii::$app->user->id])
                ->joinWith('passwords', false)
                ->joinWith('users', false)
                ->asArray(),
            'pagination' => [
                'pageSize' => $page,
            ],
        ]);
    }

    /**
     * Add student
     * 
     * @return bool
     */
    public function addStudent(): bool
    {
        $this->validate();
        
        if (!$this->hasErrors()) {
            $transaction = Yii::$app->db->beginTransaction();   
            try {
                $user = new Users();
                $user->attributes = $this->attributes;

                if ($user->addStudent()) {
                    $student_competenc = new StudentsCompetencies();
                    $student_competenc->students_id = $user->id;
                    $student_competenc->competencies_id = Yii::$app->user->id;
                    if ($student_competenc->save()) {
                        $transaction->commit();
                        return true;
                    }
                }
            } catch(\Exception $e) {
                $transaction->rollBack();
            } catch(\Throwable $e) {
                $transaction->rollBack();
            }
        }

        return false;
    }

    public function addDirStudent()
    {
        if ($this->dir_title = FileComponent::createDir()) {
            $num_modeles = Competencies::findOne(['experts_id' => $this->competencies_id])?->num_modules;
            for($i = 0; $i < $num_modeles; $i++) {
                if (!FileComponent::createDir("$this->dir_title/$this->dir_title-m" . ($i + 1))) {
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
        if (DbComponent::createUser($login, $password)) {
            $num_modeles = Competencies::findOne(['experts_id' => $this->competencies_id])?->num_modules;
            for($i = 0; $i < $num_modeles; $i++) {
                if (!DbComponent::createDb($login . '_m' . ($i + 1)) && !DbComponent::addRuleDb($login, $login . '_m' . ($i + 1))) {
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
        if (DbComponent::deleteUser($login)) {
            $num_modeles = Competencies::findOne(['experts_id' => $this->competencies_id])?->num_modules;
            for($i = 0; $i < $num_modeles; $i++) {
                if (!DbComponent::deleteDb($login . '_m' . ($i + 1))) {
                    return false;
                }
            }
            return true;
        }

        return false;
    }
}
