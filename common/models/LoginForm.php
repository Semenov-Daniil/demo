<?php

namespace common\models;

use Yii;
use yii\base\Model;

/**
 * LoginForm is the model behind the login form.
 *
 * @property-read Users|null $user
 *
 */
class LoginForm extends Model
{
    public $login;
    public $password;

    private $_user = false;


    /**
     * @return array the validation rules.
     */
    public function rules()
    {
        return [
            [['login', 'password'], 'required'],
            ['password', 'validatePassword'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels(): array
    {
        return [
            'login' => 'Логин',
            'password' => 'Пароль'
        ]; 
    }

    /**
     * Validates the password.
     * This method serves as the inline validation for password.
     *
     * @param string $attribute the attribute currently being validated
     * @param array $params the additional name-value pairs given in the rule
     */
    public function validatePassword($attribute, $params)
    {
        if (!$this->hasErrors()) {
            $user = $this->getUser();

            if (!$user || !$user->validatePassword($this->password)) {
                $this->addError($attribute, 'Неправильный «' . $this->getAttributeLabel('login') . '» или «' . $this->getAttributeLabel('password') .  '».');
            }
        }
    }

    /**
     * Logs in a user using the provided login and password.
     * @return bool whether the user is logged in successfully
     */
    public function login($role)
    {
        $this->validate();

        if (!$this->hasErrors()) {
            $user = $this->getUser();

            if ($user->role->title == $role) {
                return Yii::$app->user->login($this->getUser());
            }

            $this->addError('password', 'Неправильный «' . $this->getAttributeLabel('login') . '» или «' . $this->getAttributeLabel('password') .  '».');
        }

        return false;
    }

    /**
     * Finds user by [[login]]
     *
     * @return Users|null
     */
    public function getUser()
    {
        if ($this->_user === false) {
            $this->_user = Users::findOne(['login' => $this->login]);
        }

        return $this->_user;
    }
}
