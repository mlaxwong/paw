<?php
namespace paw\db;

use paw\db\ActiveRecordInterface;

interface CollectionInterface extends ActiveRecordInterface
{
    public static function collectionModel();

    public static function collectionTypeModel();

    public static function collectionValueModel();
    
    public static function collectionFieldModel();

    public static function typeAttribute();
}