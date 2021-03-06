<?php
namespace paw\models;

use yii\db\ActiveRecord;
use paw\models\CollectionField;
use paw\models\Collection;

class CollectionValue extends ActiveRecord
{
    public static function tableName(): string
    {
        return '{{%collection_value}}';
    }

    public function rules()
    {
        return [
            [['collection_id', 'collection_field_id'], 'required'],
            [['id', 'collection_id', 'collection_field_id'], 'integer'],
            [['value'], 'safe'],
        ];
    }

    public function getCollection()
    {
        return $this->hasOne(Collection::class, ['id' => 'collection_id']);
    }

    public function getField()
    {
        return $this->hasOne(CollectionField::class, ['id' => 'collection_field_id']);
    }
}