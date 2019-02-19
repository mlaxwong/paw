<?php
namespace paw\resources;

class CollectionType extends \paw\db\Resource
{
    public static function modelClass()
    {
        return \paw\models\CollectionType::class;
    }
}