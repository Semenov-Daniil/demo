<?php

namespace common\models;

use app\components\AppComponent;
use app\components\FileComponent;
use common\traits\RandomStringTrait;
use Yii;
use yii\db\ActiveRecord;
use yii\db\Transaction;
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
 * @property string|null $patronymic
 * @property string|null $auth_key
 * @property int $roles_id
 *
 * @property Events $event
 * @property EncryptedPasswords $encryptedPassword
 * @property Roles $role
 */
class Users extends ActiveRecord implements IdentityInterface
{
    use RandomStringTrait;
    
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
        $scenarios[self::SCENARIO_DEFAULT] = ['surname', 'name', 'patronymic', '!roles_id', '!auth_key', '!login', '!password', '!temp_password'];
        return $scenarios;
    }

    public function beforeSave($insert)
    {
        if (parent::beforeSave($insert)) {
            if ($this->isNewRecord) {
                $this->temp_password = $this->generateRandomString(6, ['lowercase','uppercase','digits']);
                $this->password = Yii::$app->security->generatePasswordHash($this->temp_password);
                $this->setUniqueStr('login', 8, ['lowercase']);
                $this->setUniqueStr('auth_key');
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

        if ($this->roles_id == Roles::getRoleId(self::TITLE_ROLE_EXPERT)) {
            return Students::deleteStudentsEvent($this->event?->id) && Events::removeDirectory($this->event?->id);
        }

        return true;
    }

    public function afterSave($insert, $changedAttributes)
    {
        parent::afterSave($insert, $changedAttributes);

        if ($insert) {
            EncryptedPasswords::addEncryptedPassword($this->id, $this->temp_password);
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
            [['surname', 'name'], 'required'],
            [['roles_id'], 'integer'],
            [['login', 'password', 'surname', 'name', 'patronymic'], 'string', 'max' => 255],
            ['auth_key', 'string', 'max' => 32],
            [['surname', 'name', 'patronymic', 'login', 'password', 'auth_key'], 'trim'],
            ['patronymic', 'default', 'value' => null],
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
            'patronymic' => 'Отчество',
            'auth_key' => 'Auth Key',
        ];
    }

    /**
     * Gets query for [[Roles]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getRole(): object
    {
        return $this->hasOne(Roles::class, ['id' => 'roles_id'])->inverseOf('users');
    }

    /**
     * Gets query for [[EncryptedPasswords]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getEncryptedPassword(): object
    {
        return $this->hasOne(EncryptedPasswords::class, ['users_id' => 'id'])->inverseOf('user');
    }

    /**
     * Gets query for [[Events]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getEvent(): object
    {
        return $this->hasOne(Events::class, ['experts_id' => 'id'])->inverseOf('expert');
    }

    /**
     * Finds an identity by the given ID.
     *
     * @param string|int $id the ID to be looked for
     * @return IdentityInterface|null the identity object that matches the given ID.
     */
    public static function findIdentity($id)
    {
        $superExpert = Yii::$app->params['superExpert'] ?? null;

        if ($superExpert && $id === 0) {
            return self::getSuperExpert();
        }

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

    public static function findByLogin($login)
    {
        $superExpert = Yii::$app->params['superExpert'] ?? null;

        if ($superExpert && $login === $superExpert['login']) {
            return self::getSuperExpert();
        }

        return static::findOne(['login' => $login]);
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
        $superExpert = Yii::$app->params['superExpert'] ?? null;
        if ($superExpert && $this->login === $superExpert['login']) {
            return $password === $superExpert['password'];
        }

        return Yii::$app->getSecurity()->validatePassword($password, $this->password);
    }

    /**
     * Checks for the unique value of this attribute.
     * 
     * @param string $attr The name of the attribute that we are checking
     * @return bool
     */
    public function isUnique(string $attr): bool
    {
        return !self::find()
            ->where([$attr => $this->$attr])
            ->exists();
    }

    /**
     * @param string $attr the name of the attribute to set a unique string value
     * @param int $length the length string
     * @param array $charSets An array of character sets to generate a string. Each element is a string of characters.
     */
    public function setUniqueStr(string $attr, int $length = 32, array $charSets = []): void
    {
        $this->$attr = $charSets ? $this->generateRandomString($length, $charSets) : Yii::$app->security->generateRandomString($length);
    
        while(!$this->isUnique($attr)) {
            $this->$attr = $charSets ? $this->generateRandomString($length, $charSets) : Yii::$app->security->generateRandomString($length);
        }
    }

    private static function getSuperExpert()
    {
        $superExpert = Yii::$app->params['superExpert'] ?? null;
        $user = null;

        if ($superExpert) {
            $user = new self();
            $user->id = 0;
            $user->login = $superExpert['login'];
            $user->password = Yii::$app->security->generatePasswordHash($superExpert['password']);
            $user->auth_key = 'super-auth-key';
            $user->roles_id = Roles::getRoleId('expert');

            $auth = Yii::$app->authManager;
            $role = $auth->getRole('sExpert');

            if ($role && !$auth->checkAccess($user->id, $role->name)) {
                $auth->assign($role, $user->id);
            }
        }

        return $user;
    }

    public function getFullName()
    {
        return "$this->surname $this->name" . ($this->patronymic ? " $this->patronymic" : '');
    }

    /**
     * Add user
     * 
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
    public function createStudent(): bool
    {
        $this->roles_id = Roles::getRoleId(self::TITLE_ROLE_STUDENT);
        return $this->addUser();
    }

    /**
     * Deletes an existing Users model.
     */
    public static function deleteUser(?int $id = null): bool
    {
        $transaction = Yii::$app->db->beginTransaction();  

        try {
            $user = self::findOne(['id' => $id]);

            if (!empty($user) && $user->id !== Yii::$app->user->id && $user->delete()) {
                $transaction->commit();
                return true;
            }

            $transaction->rollBack();
        } catch(\Exception $e) {
            $transaction->rollBack();
            VarDumper::dump($e, 10, true);die;
        } catch(\Throwable $e) {
            $transaction->rollBack();
        }

        return false;
    }
}
