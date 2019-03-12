<?php
namespace paw\models;

use Yii;
use yii\base\Model;
use paw\models\User;

class LoginForm extends Model
{
    public $username;
    public $password;

    public function rules()
    {
        return [
            [['username', 'password'], 'required'],
            [['password'], 'validatePassword'],

        ];
    }

    public function validatePassword($attribute, $params)
    {
        if (!$this->hasErrors()) 
        {
            $user = $this->getUser();
            if (!$user || !$user->validatePassword($this->password)) $this->addError($attribute, Yii::t('app', 'Incorrect username or password.'));
        }
    }

    protected function getUser()
    {
        return User::findByLogin($this->username);
    }

    public function submit()
    {
        if (!$this->validate()) return false;
        return $this->getUser();
    }
}