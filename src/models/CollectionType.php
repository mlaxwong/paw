<?php
namespace paw\models;

use Yii;
use yii\db\ActiveRecord;
use voskobovich\linker\LinkerBehavior;
use paw\models\Collection;
use paw\models\CollectionField;

class CollectionType extends ActiveRecord
{
    const MODE_LIST   = 'list';
    const MODE_SINGLE    = 'single';
    const MODE_NESTEDSET = 'nestedset';

    public function behaviors()
    {
        return [
            [
                'class' => LinkerBehavior::class,
                'relations' => ['collection_field_ids' => 'fields'],
            ],
        ];
    }

    public static function tableName(): string
    {
        return '{{%collection_type}}';
    }

    public static function getModes(): array
    {
        return [
            self::MODE_LIST      => Yii::t('app', 'Channel'),
            self::MODE_SINGLE       => Yii::t('app', 'Single'),
            self::MODE_NESTEDSET    => Yii::t('app', 'Nested Set'),
        ];
    }

    public function rules()
    {
        return [
            [['name', 'handle'], 'required'],
            [['name', 'handle'], 'string', 'max' => 256],
            [['collection_field_ids'], 'each', 'rule' => ['integer']],
            [['mode'], 'in', 'range' => array_keys(self::getModes())],
        ];
    }

    public function getCollections()
    {
        return $this->hasMany(Collection::class, ['collection_type_id' => 'id']);
    }

    public function getFields()
    {
        return $this->hasMany(CollectionField::class, ['id' => 'collection_field_id'])->viaTable('{{%collection_type_field_map}}', ['collection_type_id' => 'id']);
    }

    public function attributeLabels()
    {
        return [
            'collection_ids' => Yii::t('app', 'Collections'),
            'collection_field_ids' => Yii::t('app', 'Fields'),
        ];
    }
    
}