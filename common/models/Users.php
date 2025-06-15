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
 * @property string|null $created_at
 * @property string|null $updated_at
 * @property int $statuses_id
 *
 * @property Events $event
 * @property array $events
 * @property EncryptedPasswords $encryptedPassword
 * @property Roles $role
 * @property Statuses $statuses
 * @property Students $students
 */
class Users extends ActiveRecord implements IdentityInterface
{
    public string $temp_password = '';

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

    public function afterSave($insert, $changedAttributes)
    {
        parent::afterSave($insert, $changedAttributes);

        if ($insert) {
            EncryptedPasswords::storeEncryptedPassword($this->id, $this->temp_password);
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
            [['statuses_id'], 'exist', 'skipOnError' => true, 'targetClass' => Statuses::class, 'targetAttribute' => ['statuses_id' => 'id']],
            ['statuses_id', 'default', 'value' => Statuses::getStatusId(Statuses::CONFIGURING)],
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
            'roles_id' => 'Роль',
            'statuses_id' => 'Статус'
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
     * Gets query for [[Events]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getEvents(): object
    {
        return $this->hasMany(Events::class, ['experts_id' => 'id'])->inverseOf('expert');
    }

    /**
     * Gets query for [[Statuses]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getStatuses()
    {
        return $this->hasOne(Statuses::class, ['id' => 'statuses_id']);
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

    private static function getSuperExpert()
    {
        $superExpert = Yii::$app->params['superExpert'] ?? null;
        if (!$superExpert) {
            return null;
        }

        $user = new self();
        $user->id = 0;
        $user->login = $superExpert['login'];
        $user->password = Yii::$app->security->generatePasswordHash($superExpert['password']);
        $user->auth_key = 'super-auth-key';
        $user->roles_id = Roles::getRoleId('expert');

        $auth = Yii::$app->authManager;
        $cacheKey = 'super_expert_role_assigned';
        $isAssigned = Yii::$app->cache->getOrSet($cacheKey, function () use ($auth, $user) {
            $role = $auth->getRole('sExpert');
            if ($role && !$auth->checkAccess($user->id, $role->name)) {
                $auth->assign($role, $user->id);
                return true;
            }
            return $auth->checkAccess($user->id, 'sExpert');
        }, 3600);

        return $user;
    }

    public function getFullName()
    {
        return trim("{$this->surname} {$this->name}" . ($this->patronymic ? " {$this->patronymic}" : ''));
    }
}
