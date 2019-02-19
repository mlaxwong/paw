<?php
namespace paw\resources;

class CollectionField extends \paw\db\Resource
{
    public static function modelClass()
    {
        return \paw\models\CollectionField::class;
    }
}