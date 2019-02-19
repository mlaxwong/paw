<?php
namespace paw\collections;

class Collection extends \paw\db\Collection
{
    public static function collectionModel()
    {
        return \paw\models\Collection::class;
    }
}