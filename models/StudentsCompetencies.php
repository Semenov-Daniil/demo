<?php

namespace app\models;

use app\components\AppComponent;
use app\components\DbComponent;
use app\components\FileComponent;
use Exception;
use Yii;
use yii\data\ActiveDataProvider;
use yii\db\ActiveRecord;
use yii\helpers\VarDumper;

/**
 * This is the model class for table "dm_students_competencies".
 *
 * @property int $students_id
 * @property int $competencies_id
 * @property string $dir_prefix
 *
 * @property Competencies $competencies
 * @property Modules array $modules
 * @property Users $students
 * @property Passwords $passwords
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
                $login = $this->users->login;
                $password = $this->passwords->password;
                $this->dir_prefix = AppComponent::generateRandomString(8, ['lowercase']);

                if ($this->createAccountMySQL($login, $password) && $this->createDbsStudent($login) && $this->createDirectoryStudent($login) && $this->createDirectoriesModules($login, $this->dir_prefix) && $this->copyFilesCompetencies($login)) {
                    return true;
                }

                FileComponent::removeDirectory(Yii::getAlias('@users') . "/$login");
                
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

        $login = $this->users->login;
        if ($this->deleteDbStudent($login) && $this->deleteAccountMySQL($login)) {
            FileComponent::removeDirectory(Yii::getAlias('@users') . "/$login");
            return true;
        }
        
        return false;
    }

    public function scenarios()
    {
        $scenarios = parent::scenarios();
        $scenarios[self::SCENARIO_DEFAULT] = ['!students_id', '!competencies_id', '!dir_prefix'];
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
            [['surname', 'name', 'middle_name', 'dir_prefix'], 'string', 'max' => 255],
            [['surname', 'name', 'middle_name', 'dir_prefix'], 'trim'],
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
            'dir_prefix' => 'Директория',
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
     * Gets query for [[Modules]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getModules()
    {
        return $this->hasMany(Modules::class, ['competencies_id' => 'competencies_id']);
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
     * @param int $page page size
     * 
     * @return ActiveDataProvider
     */
    public static function getDataProviderStudents(int $page): ActiveDataProvider
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
                    'dir_prefix',
                ])
                ->where(['competencies_id' => Yii::$app->user->id])
                ->joinWith('passwords', false)
                ->joinWith('users', false)
                ->asArray(),
            'pagination' => [
                'pageSize' => $page,
            ],
        ]);
    }

    /**
     * Returns the full name of the student module directory.
     * 
     * @param string $prefix prefix directory.
     * @param int $numberModule module number.
     * @return string full name directory.
     */
    public function getDirectoryModuleTitle(string $prefix, int $numberModule): string
    {
        return "{$prefix}-m{$numberModule}";
    }

    /**
     * Returns the full name of the student module database.
     * 
     * @param string $login login student.
     * @param int $numberModule module number.
     * @return string full name database.
     */
    public function getDbTitle(string $login, int $numberModule): string
    {
        return "{$login}_m{$numberModule}";
    }

    /**
     * Adds a new user with the `student` role
     * 
     * @return bool returns the value `true` if the student has been successfully added.
     * 
     * @throws Exception|Throwable throws an exception if an error occurs when adding a student.
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
                FileComponent::removeDirectory(Yii::getAlias('@users') . "/$user->login");
                $transaction->rollBack();
            } catch(\Throwable $e) {
                FileComponent::removeDirectory(Yii::getAlias('@users') . "/$user->login");
                $transaction->rollBack();
            }
        }

        return false;
    }

    /**
     * Creates a student directory.
     * 
     * @param $login login student.
     * 
     * @return bool returns the value `true` if the student directory has been successfully created.
     */
    public function createDirectoryStudent(string $login): bool
    {
        return FileComponent::createDirectory(Yii::getAlias('@users') . "/$login");
    }

    /**
     * Creates a modules directories.
     * 
     * @param string $login login student.
     * @param string $prefix prefix directory.
     * 
     * @return bool returns the value `true` if the modules directories has been successfully created.
     */
    public function createDirectoriesModules(string $login, string $prefix): bool
    {
        $numberModule = count($this->competencies->modules);
        for($i = 0; $i < $numberModule; $i++) {
            if (!FileComponent::createDirectory(Yii::getAlias('@users') . "/$login/" . $this->getDirectoryModuleTitle($prefix, $i+1))) {
                return false;
            }
        }
        return true;
    }

    /**
     * Deletes a module directory.
     * 
     * @param string $login login student.
     * @param string $prefix prefix directory.
     * 
     * @return void
     */
    public function deleteDirectoryModule(string $login, int $numberModule): void
    {
        FileComponent::removeDirectory(Yii::getAlias('@users') . "/$login/" . $this->getDirectoryModuleTitle($this->dir_prefix, $numberModule));
    }

    /**
     * Copies competence files to the student.
     * 
     * @param $login student's login.
     * 
     * @return bool `true` on success or `false` on failure.
     * 
     * @throws Exception throws an exception if an error occurs when copying files.
     */
    public function copyFilesCompetencies(string $login): bool
    {
        try {
            $files = $this->competencies->filesCompetencies;

            if (!empty($files)) {
                $competencePath = Yii::getAlias('@competencies') . "/" . $this->competencies->dir_title;
                $studentPath = Yii::getAlias('@users') . "/$login/public";
    
                if (!is_dir($studentPath)) {
                    FileComponent::createDirectory($studentPath);
                }
    
                foreach ($files as $file) {
                    if (!copy("$competencePath/$file->title.$file->extension", "$studentPath/$file->title.$file->extension")) {
                        throw new Exception("Failed to copy file from $competencePath/$file->title.$file->extension to $studentPath/$file->title.$file->extension");
                    }
                }
            }

            return true;
        } catch (\Exception $e) {
            throw $e;
        }

        return false;
    }

    /**
     * Creates a new MySQL user using `app\components\DbComponent::createUser()`.
     * 
     * @param string $login new user login.
     * @param string $password new user password.
     * 
     * @return bool returns `true` if the user was successfully created.
     * 
     * @throws Exception|Throwable throws an exception if an error occurred while creating a account.
     */
    public function createAccountMySQL(string $login, string $password): bool
    {
        return DbComponent::createUser($login, $password);
    }

    /**
     * Deletes a new MySQL user using `app\components\DbComponent::deleteUser()`.
     * 
     * @param string $login new user login.
     * 
     * @return bool returns `true` if the user was successfully deleted.
     * 
     * @throws Exception|Throwable throws an exception if an error occurred while deleting a account.
     */
    public function deleteAccountMySQL(string $login): bool
    {
        return DbComponent::deleteUser($login);
    }

    /**
     * Creates student databases.
     * 
     * @param string $login new user login.
     * 
     * @return bool returns `true` if the user was successfully created.
     * 
     * @throws Exception|Throwable throws an exception if an error occurred while creating a databases.
     */
    public function createDbsStudent(string $login): bool
    {
        $modules = $this->modules;
        for($i = 0; $i < count($modules); $i++) {
            if (!DbComponent::createDb($this->getDbTitle($login, $i+1)) || ($modules[$i]->status && !DbComponent::grantPrivileges($login, $this->getDbTitle($login, $i+1)))) {
                return false;
            }
        }
        return true;
    }

    /**
     * Deletes student databases.
     * 
     * @param string $login new user login.
     * 
     * @return bool returns `true` if the user was successfully created.
     * 
     * @throws Exception|Throwable throws an exception if an error occurred while deleting a databases.
     */
    public function deleteDbStudent(string $login): bool
    {
        $modules = $this->modules;
        for($i = 0; $i < count($modules); $i++) {
            if (!DbComponent::deleteDb($this->getDbTitle($login, $i+1))) {
                return false;
            }
        }
        return true;
    }

    /**
     * Grants privileges to the database given to the student.
     * 
     * @param string $login new user login.
     * @param int $numberModule module number.
     * 
     * @return bool returns `true` if privileges were successfully granted.
     */
    public function grantPrivilegesDbStudent(string $login, int $numberModule): bool
    {
        return DbComponent::grantPrivileges($login, $this->getDbTitle($login, $numberModule));
    }

    /**
     * Revokes all the user's privileges on the database given to the student.
     * 
     * @param string $login new user login.
     * @param int $numberModule module number.
     * 
     * @return bool returns `true` if the privileges were successfully revoked.
     */
    public function revokePrivilegesDbStudent(string $login, int $numberModule): bool
    {
        return DbComponent::revokePrivileges($login, $this->getDbTitle($login, $numberModule));
    }

    /**
     * Deletes the student.
     * 
     * @param string|null $id student ID.
     * 
     * @return bool returns the value `true` if the student was successfully deleted.
     */
    public static function deleteStudent(string|null $id = null): bool
    {
        if (!is_null($id)) {
            return Users::deleteUser($id);
        }
        return false;
    }
}
