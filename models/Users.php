<?php

namespace app\models;

use Yii;

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
    const SCENARIO_REGISTER = "register";

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
            [['login', 'password'], 'required'],
            [['id', 'roles_id'], 'integer'],
            [['login', 'password', 'surname', 'name', 'middle_name', 'token'], 'string', 'max' => 255],
            [['token'], 'unique'],
            [['roles_id'], 'exist', 'skipOnError' => true, 'targetClass' => Roles::class, 'targetAttribute' => ['roles_id' => 'id']],
        
            [['surname', 'name'], 'required', 'on' => static::SCENARIO_REGISTER],
            [['login'], 'unique', 'on' => static::SCENARIO_REGISTER],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'login' => 'Login',
            'password' => 'Password',
            'surname' => 'Surname',
            'name' => 'Name',
            'middle_name' => 'Middle Name',
            'token' => 'Token',
        ];
    }

    /**
     * Gets query for [[Roles]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getRoles()
    {
        return $this->hasOne(Roles::class, ['id' => 'roles_id']);
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
        return static::findOne(['token' => $token]);
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
        // return $this->auth_key;
    }

    /**
     * @param string $authKey
     * @return bool|null if auth key is valid for current user
     */
    public function validateAuthKey($authKey)
    {
        // return $this->getAuthKey() === $authKey;
    }

    /**
     * @param string $password
     * @return bool
     */
    public function validatePassword($password)
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

    public static function login()
    {
        $answer = [
            'status' => false,
            'model' => new Users(),
        ];

        $model = &$answer['model'];

        if (Yii::$app->request->isPost) {
            $data = Yii::$app->request->post()['Users'];
    
            $model->load($data, '');
            $model->validate();
    
            
            if (!$model->hasErrors()) {
                $user = Users::findOne(['login' => $model->login]);
                
                
                if (!empty($user) && $user->validatePassword($model->password)) {
                    $model = $user;
                    $model->token = Yii::$app->security->generateRandomString();
    
                    while(!$model->isUnique('token')) {
                        $model->token = Yii::$app->security->generateRandomString();
                    }
    
                    Yii::$app->user->login($model);
    
                    if ($model->save()) {
                        $asnwer['status'] = true;
                    }
                } else {
                    $model->addError('password', 'Неправильное имя пользователя или пароль.');
                }
            }
        }

        $model->password = '';

        return $answer;
    }

    public static function logout()
    {
        if (!Yii::$app->user->isGuest) {
            $identity = Yii::$app->user->identity;
            $user = Users::findOne($identity->id);
            $user->token = null;
            $user->save(false);
            Yii::$app->user->logout();
        }
        
    }
}
