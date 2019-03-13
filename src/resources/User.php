<?php
namespace paw\resources;

class User extends \paw\db\Resource
{
    public static function modelClass()
    {
        return \paw\models\User::class;
    }
}