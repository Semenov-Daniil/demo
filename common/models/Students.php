<?php

namespace common\models;

use app\components\AppComponent;
use app\components\DbComponent;
use app\components\FileComponent;
use common\traits\RandomStringTrait;
use Exception;
use Yii;
use yii\data\ActiveDataProvider;
use yii\db\ActiveRecord;
use yii\helpers\VarDumper;

/**
 * This is the model class for table "dm_students".
 *
 * @property int $students_id
 * @property int $events_id
 * @property string $dir_prefix
 *
 * @property Events $event
 * @property Modules[] $modules
 * @property Users $user
 * @property EncryptedPasswords $encryptedPassword
 */
class Students extends ActiveRecord
{
    use RandomStringTrait;

    public string $surname = '';
    public string $name = '';
    public string|null $patronymic = '';

    const SCENARIO_CREATE = "create";
    const SCENARIO_UPDATE = "update";
    const TITLE_ROLE_STUDENT = "student";

    public function beforeSave($insert)
    {
        if (parent::beforeSave($insert)) {
            if ($this->isNewRecord) {
                $login = $this->user?->login;
                $password = EncryptedPasswords::decryptByPassword($this->encryptedPassword->encrypted_password);
                $this->dir_prefix = $this->generateRandomString(8, ['lowercase']);

                if ($this->createAccountMySQL($login, $password) && $this->createDbsStudent($login) && $this->createDirectoryStudent($login) && $this->createDirectoriesModules($login, $this->dir_prefix) && $this->copyFilesEvents($login)) {
                    return true;
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

        $login = $this->user->login;

        if ($this->deleteDbStudent($login) && $this->deleteAccountMySQL($login)) {
            Yii::$app->fileComponent->removeDirectory(Yii::getAlias('@students') . "/$login");
            return true;
        }
        
        return false;
    }

    public function scenarios()
    {
        $scenarios = parent::scenarios();
        $scenarios[self::SCENARIO_DEFAULT] = ['surname', 'name', 'patronymic', '!students_id', '!events_id', '!dir_prefix'];
        $scenarios[self::SCENARIO_CREATE] = ['surname', 'name', 'patronymic', 'events_id'];
        $scenarios[self::SCENARIO_UPDATE] = ['surname', 'name', 'patronymic'];
        return $scenarios;
    }

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%students}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['surname', 'name', 'students_id'], 'required'],
            ['events_id', 'required', 'message' => 'Необходимо выбрать чемпионат.'],
            [['surname', 'name', 'patronymic', 'dir_prefix'], 'string', 'max' => 255],
            [['surname', 'name', 'patronymic', 'dir_prefix'], 'trim'],
            [['students_id', 'events_id'], 'integer'],
            ['patronymic', 'default', 'value' => null],
            [['events_id'], 'exist', 'skipOnError' => true, 'targetClass' => Events::class, 'targetAttribute' => ['events_id' => 'id']],
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
            'events_id' => 'Чемпионат',
            'dir_prefix' => 'Директория',
            'surname' => 'Фамилия',
            'name' => 'Имя',
            'patronymic' => 'Отчество',
        ];
    }

    public function attributes() {
        return [
            ...parent::attributes(),
            'surname',
            'name',
            'patronymic'
        ];
    }

    /**
     * Gets query for [[Events]].
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
    public function getModules()
    {
        return $this->hasMany(Modules::class, ['events_id' => 'events_id']);
    }

    /**
     * Gets query for [[Users]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getUser()
    {
        return $this->hasOne(Users::class, ['id' => 'students_id']);
    }

    /**
     * Gets query for [[Passwords]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getEncryptedPassword(): object
    {
        return $this->hasOne(EncryptedPasswords::class, ['users_id' => 'students_id'])->inverseOf('user');
    }

    /**
     * Get DataProvider students
     * 
     * @param int $records number of records.
     * 
     * @return ActiveDataProvider
     */
    public static function getDataProviderStudents(string|int|null $eventID = null, int $records = 10): ActiveDataProvider
    {
        return new ActiveDataProvider([
            'query' => self::find()
                ->select([
                    'students_id',
                    'CONCAT(surname, \' \', name, COALESCE(CONCAT(\' \', patronymic), \'\')) AS fullName',
                    'login',
                    EncryptedPasswords::tableName() . '.encrypted_password AS encryptedPassword',
                ])
                ->where(['events_id' => $eventID])
                ->joinWith('encryptedPassword', false)
                ->joinWith('user', false)
                ->asArray()
            ,
            'pagination' => [
                'pageSize' => $records,
                'route' => 'students',
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

    public function getDirectoryModuleFileTitle(int $numberModule): string
    {
        return "module-{$numberModule}";
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

    // public static function encryptById(int $id): string
    // {
    //     return base64_encode(Yii::$app->security->encryptByKey($id, Yii::$app->params['studentKey']));
    // }

    // public static function decryptById(string $id): string
    // {
    //     return Yii::$app->security->decryptByKey(base64_decode($id), Yii::$app->params['studentKey']);
    // }

    public function deleteDataStudent(): bool
    {
        try {
            $login = $this->user->login;

            Yii::$app->fileComponent->removeDirectory(Yii::getAlias("@students/$login"));
            $this->deleteDbStudent($login);
            $this->deleteAccountMySQL($login);
            return Users::deleteUser($this->students_id);
        } catch (\Exception $e) {
        } catch(\Throwable $e) {
        }

        return false;
    }

    /**
     * Adds a new user with the `student` role
     * 
     * @return bool returns the value `true` if the student has been successfully added.
     * 
     * @throws Exception|Throwable throws an exception if an error occurs when adding a student.
     */
    public function createStudent(): bool
    {
        $this->validate();
        
        if (!$this->hasErrors()) {
            $transaction = Yii::$app->db->beginTransaction();

            try {
                $user = new Users();
                $user->attributes = $this->attributes;

                if ($user->createStudent()) {
                    $this->students_id = $user->id;

                    if ($this->save()) {
                        $transaction->commit();
                        return true;
                    }
                }
                
                $this->deleteDataStudent();
                $transaction->rollBack();
            } catch(\Exception $e) {
                $transaction->rollBack();
                $this->deleteDataStudent();
                VarDumper::dump( $e, $depth = 10, $highlight = true);die;
            } catch(\Throwable $e) {
                $transaction->rollBack();
                $this->deleteDataStudent();
                VarDumper::dump( $e, $depth = 10, $highlight = true);die;
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
        return Yii::$app->fileComponent->createDirectory(Yii::getAlias('@students') . "/$login") 
                && Yii::$app->fileComponent->createDirectory(Yii::getAlias('@students') . "/$login/public");
    }

    public function createDirectoriesModule($module)
    {
        return Yii::$app->fileComponent->createDirectory(Yii::getAlias('@students/' . $this->user->login . '/' . $this->getDirectoryModuleTitle($this->dir_prefix, $module->number)))
                && Yii::$app->fileComponent->createDirectory(Yii::getAlias('@students/' . $this->user->login . '/public/' . $this->getDirectoryModuleFileTitle($module->number)));
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
        $modules = $this->event->modules;

        foreach ($modules as $module) {
            if (!$this->createDirectoriesModule($module)) {
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
        Yii::$app->fileComponent->removeDirectory(Yii::getAlias('@students') . "/$login/" . $this->getDirectoryModuleTitle($this->dir_prefix, $numberModule));
    }

    public function clearDirectoryModule(string $login, int $numberModule): void
    {
        Yii::$app->fileComponent->clearDirectory(Yii::getAlias('@students') . "/$login/" . $this->getDirectoryModuleTitle($this->dir_prefix, $numberModule), false);
    }

    /**
     * Copies event files to the student.
     * 
     * @param $login student's login.
     * 
     * @return bool `true` on success or `false` on failure.
     * 
     * @throws Exception throws an exception if an error occurs when copying files.
     */
    public function copyFilesEvents(string $login): bool
    {
        $event = $this->event;

        if (!empty($event->files)) {
            $eventPath = Yii::getAlias("@events/$event->dir_title");
            $studentPath = Yii::getAlias("@students/$login/public");

            if (!is_dir($studentPath)) {
                Yii::$app->fileComponent->createDirectory($studentPath);
            }

            foreach ($event->files as $file) {
                if (!copy("$eventPath/$file->save_name.$file->extension", "$studentPath/$file->save_name.$file->extension")) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Creates a new MySQL user using `app\components\Yii::$app->dbComponent->createUser()`.
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
        return Yii::$app->dbComponent->createUser($login, $password);
    }

    /**
     * Deletes a new MySQL user using `app\components\Yii::$app->dbComponent->deleteUser()`.
     * 
     * @param string $login new user login.
     * 
     * @return bool returns `true` if the user was successfully deleted.
     * 
     * @throws Exception|Throwable throws an exception if an error occurred while deleting a account.
     */
    public function deleteAccountMySQL(string $login): bool
    {
        return Yii::$app->dbComponent->deleteUser($login);
    }

    public function createDbModule($module)
    {
        return Yii::$app->dbComponent->createDb($this->getDbTitle($this->user->login, $module->number)) && Yii::$app->dbComponent->grantPrivileges($this->user->login, $this->getDbTitle($this->user->login, $module->number));
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

        foreach ($modules as $module) {
            if (!Yii::$app->dbComponent->createDb($this->getDbTitle($login, $module->number)) || ($module->status && !Yii::$app->dbComponent->grantPrivileges($login, $this->getDbTitle($login, $module->number)))) {
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
    public  function deleteDbStudent(string $login): bool
    {
        $modules = $this->modules;

        foreach ($modules as $module) {
            if (!Yii::$app->dbComponent->deleteDb($this->getDbTitle($login, $module->number))) {
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
        return Yii::$app->dbComponent->grantPrivileges($login, $this->getDbTitle($login, $numberModule));
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
        return Yii::$app->dbComponent->revokePrivileges($login, $this->getDbTitle($login, $numberModule));
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
            return self::findOne(['students_id' => $id])?->deleteDataStudent();
        }
        return false;
    }

    public static function deleteStudents(array $studentsID): bool
    {
        foreach ($studentsID as $id) {
            if (!self::deleteStudent($id)) {
                return false;
            }
        }

        return true;
    }

    public static function deleteStudentsEvent(int|array|null $eventsID)
    {
        $students = self::find()
            ->where(['events_id' => $eventsID])
            ->all()
        ;

        foreach ($students as $student) {
            if (!$student->deleteDataStudent()) {
                return false;
            }
        }

        return true;
    }

    public static function getExportStudents(int|string $eventID): array
    {
        return self::find()
            ->select([
                'students_id',
                'CONCAT(surname, \' \', name, COALESCE(CONCAT(\' \', patronymic), \'\')) AS fullName',
                'login',
                EncryptedPasswords::tableName() . '.encrypted_password',
            ])
            ->where(['events_id' => $eventID])
            ->joinWith('encryptedPassword', false)
            ->joinWith('user', false)
            ->asArray()
            ->all()
        ;
    }

    public static function findStudent(?int $id = null): static|null
    {
        $model = null;

        if ($user = Users::findOne(['id' => $id])) {
            $model = new self();
            $model->attributes = $user->attributes;
        }

        return $model;
    }

    public function updateStudent(?int $id = null): bool
    {
        $this->validate();

        if (!$this->hasErrors()) {
            $user = Users::findOne(['id' => $id]);
            
            if (!empty($user)) {
                $user->attributes = $this->attributes;
                return $user->save();
            }
        }

        return false;
    }
}
