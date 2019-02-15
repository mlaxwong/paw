<?php
namespace paw\models;

use yii\db\ActiveRecord;
use paw\models\CollectionType;
use paw\base\Field;

class CollectionField extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%collection_field}}';
    }

    public function rules()
    {
        return [
            [['name', 'handle', 'config_class'], 'required'],
            [['name', 'handle'], 'string', 'max' => 256],
            // [['config_class'], 'in', 'range' => array_keys($this->getFieldTypes())],
            [['config'], 'safe'],
        ];  
    }

    public function getFieldTypes()
    {
        return Field::getFieldTypes();
    }

    public function getCollectionTypes()
    {
        return $this->hasMany(CollectionType::class, ['id' => 'collection_type_id'])->viaTable('{{%collection_type_field_map}}', ['collection_field_id' => 'id']);
    }
}