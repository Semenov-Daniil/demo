<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;
use yii\helpers\VarDumper;
use yii\web\IdentityInterface;

/**
 * This is the model class for table "{{%users}}".
 *
 * @property int $id
 * @property string $login
 * @property string $password
 * @property string $surname
 * @property string $name
 * @property string|null $middle_name
 * @property string|null $auth_key
 * @property int $roles_id
 *
 * @property Competencies $competencies
 * @property Passwords $passwords
 * @property Roles $roles
 */
class Users extends ActiveRecord implements IdentityInterface
{
    public string $temp_password = '';

    const TITLE_ROLE_EXPERT = "expert";
    const TITLE_ROLE_STUDENT = "student";

    public function fields()
    {
        $fields = parent::fields();
        unset($fields['auth_key'], $fields['password']);
        return $fields;
    }

    public function scenarios()
    {
        $scenarios = parent::scenarios();
        $scenarios[self::SCENARIO_DEFAULT] = ['surname', 'name', 'middle_name', '!roles_id', '!auth_key', '!login', '!password', '!temp_password'];
        return $scenarios;
    }

    public function beforeSave($insert)
    {
        if (parent::beforeSave($insert)) {
            if ($this->isNewRecord) {
                $this->temp_password = Yii::$app->generationString->generateRandomString(6);
                $this->password = Yii::$app->security->generatePasswordHash($this->temp_password);
                $this->login = $this->getUniqueStr('login', 8, true, 'ONLI_SMALL_CHARACTERS');
                $this->auth_key = $this->getUniqueStr('auth_key');
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

        if ($this->roles_id == Roles::getRoleId(self::TITLE_ROLE_STUDENT)) {
            return StudentsCompetencies::findOne(['students_id' => $this->id])->delete();
        }

        if ($this->roles_id == Roles::getRoleId(self::TITLE_ROLE_EXPERT)) {
            $students = StudentsCompetencies::findAll(['competencies_id' => $this->id]);

            foreach ($students as $student) {
                if (!$student->delete()) {
                    return false;
                }
            }
        }

        return true;
    }

    public function afterSave($insert, $changedAttributes)
    {
        parent::afterSave($insert, $changedAttributes);

        if ($insert) {
            $password = new Passwords();
            $password->users_id = $this->id;
            $password->password = $this->temp_password;
            $password->save();
        }
    }

    /**
     * {@inheritdoc}
     */
    public static function tableName(): string
    {
        return '{{%users}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules(): array
    {
        return [
            [['roles_id'], 'integer'],
            [['login', 'password', 'surname', 'name', 'middle_name'], 'string', 'max' => 255],
            ['auth_key', 'string', 'max' => 32],
            [['surname', 'name'], 'required'],
            [['roles_id'], 'exist', 'skipOnError' => true, 'targetClass' => Roles::class, 'targetAttribute' => ['roles_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels(): array
    {
        return [
            'id' => 'ID',
            'login' => 'Логин',
            'password' => 'Пароль',
            'surname' => 'Фамилия',
            'name' => 'Имя',
            'middle_name' => 'Отчество',
            'auth_key' => 'Auth Key',
        ];
    }

    /**
     * Gets query for [[Roles]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getRoles(): object
    {
        return $this->hasOne(Roles::class, ['id' => 'roles_id'])->inverseOf('users');
    }

    /**
     * Gets query for [[Passwords]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getPasswords(): object
    {
        return $this->hasOne(Passwords::class, ['users_id' => 'id'])->inverseOf('users');
    }

    /**
     * Gets query for [[Competencies]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getCompetencies(): object
    {
        return $this->hasOne(Competencies::class, ['users_id' => 'id'])->inverseOf('users');
    }

    /**
     * Finds an identity by the given ID.
     *
     * @param string|int $id the ID to be looked for
     * @return IdentityInterface|null the identity object that matches the given ID.
     */
    public static function findIdentity($id)
    {
        return static::findOne($id);
    }

    /**
     * Finds an identity by the given token.
     *
     * @param string $token the token to be looked for
     * @return IdentityInterface|null the identity object that matches the given token.
     */
    public static function findIdentityByAccessToken($token, $type = null)
    {
        return static::findOne(['access_token' => $token]);
    }

    /**
     * @return int|string current user ID
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string|null current user auth key
     */
    public function getAuthKey()
    {
        return $this->auth_key;
    }

    /**
     * @param string $authKey
     * @return bool|null if auth key is valid for current user
     */
    public function validateAuthKey($authKey)
    {
        return $this->getAuthKey() === $authKey;
    }

    /**
     * @param string $password
     * @return bool
     */
    public function validatePassword($password): bool
    {
        return Yii::$app->getSecurity()->validatePassword($password, $this->password);
    }

    /**
     * @param string $attr the name of the attribute that we are checking
     * @param mixed $value value attribute
     * @return bool
     */
    public function isUnique(string $attr, mixed $value): bool
    {
        return !self::find()
            ->where([$attr => $value])
            ->exists();
    }

    /**
     * @param string $attr the name of the attribute to set a unique string value
     * @param int $length the length string
     * @param bool $builtin use the built-in function
     * @param string $flag if $b = true, the characters to use when generating
     * @return string
     */
    public function getUniqueStr(string $attr, int $length = 32, bool $builtin = false, string $flag = ''): string
    {
        $result_str = $builtin ? Yii::$app->generationString->generateRandomString($length, $flag) : Yii::$app->security->generateRandomString($length);
    
        while(!$this->isUnique($attr, $result_str)) {
            $result_str = $builtin ? Yii::$app->generationString->generateRandomString($length, $flag) : Yii::$app->security->generateRandomString($length);
        }

        return $result_str;
    }

    /**
     * Add user
     * 
     * @param array $data 
     * @return bool
     */
    public function addUser(): bool
    {
        $this->validate();

        if (!$this->hasErrors()) {
            return $this->save();
        }

        return false;
    }

    /**
     * Add expert
     * 
     * @return bool
     */
    public function addExpert(): bool
    {
        $this->roles_id = Roles::getRoleId(self::TITLE_ROLE_EXPERT);
        return $this->addUser();
    }

    /**
     * Add student
     * 
     * @return bool
     */
    public function addStudent(): bool
    {
        $this->roles_id = Roles::getRoleId(self::TITLE_ROLE_STUDENT);
        return $this->addUser();
    }

    /**
     * Deletes an existing Users model.
     */
    public function deleteUser(): bool
    {
        if (Yii::$app->user->id !== $this->id) {
            $transaction = Yii::$app->db->beginTransaction();   
            try {
                (self::findOne(['id' => $this->id]))->delete();
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
}
