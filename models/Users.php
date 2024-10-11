<?php

namespace app\models;

use Yii;
use yii\data\ActiveDataProvider;
use yii\helpers\VarDumper;

/**
 * This is the model class for table "{{%users}}".
 *
 * @property int $id
 * @property string $login
 * @property string $password
 * @property string $surname
 * @property string $name
 * @property string|null $middle_name
 * @property string|null $token
 * @property int $roles_id
 *
 * @property Role $roles
 */
class Users extends \yii\db\ActiveRecord implements \yii\web\IdentityInterface
{
    public string $temp_password = '';

    const SCENARIO_ADD = "add";
    const TITLE_ROLE_EXPERT = 'expert';
    const TITLE_ROLE_STUDENT = 'student';

    public function beforeSave($insert)
    {
        if (parent::beforeSave($insert)) {
            if ($this->isNewRecord) {
                $this->password = Yii::$app->security->generatePasswordHash($this->temp_password);
                $this->login = Yii::$app->security->generateRandomString(6);
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
           !Passwords::addPassword(['users_id' => $this->id, 'password' => $this->temp_password]) && $this->delete();
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
            [['id', 'roles_id'], 'integer'],
            [['login', 'password', 'surname', 'name', 'middle_name', 'token'], 'string', 'max' => 255],
            [['login', 'token'], 'unique'],
            ['middle_name', 'default', 'value' => null],
            [['roles_id'], 'exist', 'skipOnError' => true, 'targetClass' => Roles::class, 'targetAttribute' => ['roles_id' => 'id']],
            
            [['login', 'password'], 'required', 'except' => self::SCENARIO_ADD],
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
            'token' => 'Token',
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
     * Gets query for [[Roles]].
     *
     * @return string
     */
    public function getTitleRoles(): string
    {
        return Roles::findOne($this->roles_id)->title;
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
     * Gets query for [[Testings]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getTestings(): object
    {
        return $this->hasOne(Testings::class, ['users_id' => 'id'])->inverseOf('users');
    }


    /**
     * Finds an identity by the given ID.
     *
     * @param string|int $id the ID to be looked for
     * @return IdentityInterface|null the identity object that matches the given ID.
     */
    public static function findIdentity($id): object|null
    {
        return static::findOne($id);
    }

    /**
     * Finds an identity by the given token.
     *
     * @param string $token the token to be looked for
     * @return IdentityInterface|null the identity object that matches the given token.
     */
    public static function findIdentityByAccessToken($token, $type = null): object|null
    {
        return static::findOne(['token' => $token]);
    }

    /**
     * @return int|string current user ID
     */
    public function getId(): int|string
    {
        return $this->id;
    }

    /**
     * @return string|null current user auth key
     */
    public function getAuthKey(): string|null
    {
        return $this->token;
    }

    /**
     * @param string $authKey
     * @return bool|null if auth key is valid for current user
     */
    public function validateAuthKey($authKey): string|null
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
     * @return bool
     */
    public function isUnique(string $attr): bool
    {
        return !Users::find()
            ->where([$attr => $this->$attr])
            ->exists();
    }
    
    /**
     * @param string $attr the name of the attribute to set a unique string value
     * @param int $length the length string
     * @return bool
     */
    public function setUniqueStr(string $attr, int $length = 32): void
    {
        $this->$attr = Yii::$app->security->generateRandomString($length);
    
        while(!$this->isUnique($attr)) {
            $this->$attr = Yii::$app->security->generateRandomString($length);
        }
    }

    public static function getDataProvider($page)
    {
        return new ActiveDataProvider([
            'query' => Users::find()
                ->select([
                    'surname',
                    'name',
                    'middle_name',
                    'login',
                    Passwords::tableName() . '.password',
                    Testings::tableName() . '.title',
                    Testings::tableName() . '.num_modules',
                ])
                ->joinWith('passwords', false)
                ->joinWith('testings', false),
            'pagination' => [
                'pageSize' => $page,
            ],
        ]);
    }

    /**
     * Login user
     * 
     * @return array
     */
    static function login(): array
    {
        $answer = [
            'status' => false,
            'model' => new Users(),
        ];

        $model = &$answer['model'];

        if (Yii::$app->request->isAjax) {
            $model->load(Yii::$app->request->post(), $model->formName());
            $model->validate();
    
            if (!$model->hasErrors()) {
                $user = Users::findOne(['login' => $model->login]);
                
                if (!empty($user) && $user->validatePassword($model->password)) {
                    $model = $user;
                    $model->setUniqueStr('token');
                    $answer['status'] = $model->save();
                    Yii::$app->user->login($model);
                } else {
                    $model->addError('password', 'Неправильный «' . $model->getAttributeLabel('login') . '» или «' . $model->getAttributeLabel('password') .  '».');
                }
            }
        }

        $model->password = '';

        return $answer;
    }

    /**
     * Logout user
     * 
     * @return void
     */
    public static function logout(): void
    {
        if ($user = Users::findOne(Yii::$app->user->id)) {
            $user->token = null;
            $user->save(false);
            Yii::$app->user->logout();
        }
    }

    /**
     * Add user
     * 
     * @param array $data 
     * @return array
     */
    public static function addUser(array $data = []): array
    {
        $answer = [
            'status' => false,
            'model' => new Users()
        ];

        $user = &$answer['model'];

        $user->load($data, '');
        $user->validate();

        if (!$user->hasErrors()) {
            $user->temp_password = Yii::$app->security->generateRandomString(6);

            $answer['status'] = $user->save();
        }

        return $answer;
    }

    /**
     * Add expert
     * 
     * @return array
     */
    public static function addExpert(): array
    {
        $answer = [
            'user' => new Users(),
            'testing' => new Testings()
        ];

        $user = &$answer['user'];
        $testing = &$answer['testing'];

        $testing->scenario = Testings::SCENARIO_ADD;

        if (Yii::$app->request->isAjax) {
            $user->roles_id == Roles::getRoleId(self::TITLE_ROLE_EXPERT);

            $userAdd = Users::addUser((Yii::$app->request->post())[$user->formName()]);
            
            if ($userAdd['status']) {
                $testAdd = Testings::addTesting(['users_id' => $userAdd['model']->id, ...((Yii::$app->request->post())[$testing->formName()])]);
                if ($testAdd['status']) {
                    $user = new Users();
                    $testing = new Testings();
                } else {
                    $user->delete();
                    $testing = $testAdd['model'];
                }
            } else {
                $user = $userAdd['model'];
            }
        }
        
        return $answer;
    }

    /**
     * Deletes an existing Users model.
     */
    public static function deleteUser($id): void
    {
        (self::findOne(['id' => $id]))->delete();
    }
}
