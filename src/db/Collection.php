<?php
namespace paw\db;

use yii\db\BaseActiveRecord;
use yii\db\StaleObjectException;
use yii\helpers\Inflector;
use yii\helpers\StringHelper;
use yii\helpers\ArrayHelper;
use paw\db\CollectionInterface;
use paw\db\CollectionQuery;

class Collection extends BaseActiveRecord implements CollectionInterface
{
    const OP_INSERT = 0x01;
    const OP_UPDATE = 0x02;
    const OP_DELETE = 0x04;
    const OP_ALL    = 0x07;

    public $typeId;

    private $_oldAttributes;

    public static function instantiate($row)
    {
        return new static(['typeId' => $row[static::typeAttribute()]]);
    }

    public static function collectionModel() 
    {
        return 'paw\\models\\' . Inflector::camelize(StringHelper::basename(get_called_class()));
    }

    public static function collectionTypeModel()
    {
        return static::collectionModel() . 'Type';
    }

    public static function collectionValueModel()
    {
        return static::collectionModel() . 'Value';
    }

    public static function collectionFieldModel()
    {
        return static::collectionModel() . 'Field';
    }

    public static function fkCollectionId()
    {
        $collectionClass = static::collectionModel();
        return str_replace(['{{%', '}}'], '', $collectionClass::tableName()) . '_id';
    }

    public static function fkFieldId()
    {
        $fieldClass = static::collectionFieldModel();
        return str_replace(['{{%', '}}'], '', $fieldClass::tableName()) . '_id';
    }

    public static function typeAttribute()
    {
        return Inflector::camel2id(StringHelper::basename(static::class), '_') . '_type_id';
    }

    public function attributes()
    {
        return ArrayHelper::merge($this->getBaseAttributes(), $this->getCustomAttributes());
    }

    public function getBaseAttributes()
    {
        $modelClass = $this->collectionModel();
        return array_keys($modelClass::getTableSchema()->columns);
    }

    public function getCustomAttributes()
    {
        $type = $this->getType();
        return  $type ? ArrayHelper::getColumn($type->fields, 'handle') : [];
    }

    public function baseRules()
    {
        $modelClass = $this->collectionModel();
        return (new $modelClass)->rules();
    }

    public function customRules()
    {
        $rules = [];
        $type = $this->getType();
        if ($type) {
            foreach ($type->fields as $field)
            {
                $config = json_decode($field->config, true);
                if (json_last_error() == JSON_ERROR_NONE)
                {
                    foreach ($config as $rule)
                    {
                        array_unshift($rule, $field->handle);
                        $rules[] = $rule;
                    }
                }
            }
        }
        return $rules;
    }

    public function rules()
    {
        return ArrayHelper::merge($this->baseRules(), $this->customRules());
    }

    public function getType()
    {
        $typeClass = static::collectionTypeModel();
        return $typeClass::findOne($this->typeId);
    }

    public static function primaryKey()
    {
        $modelClass = static::collectionModel();
        return $modelClass::primaryKey();
    }

    public static function find() 
    {
        return new CollectionQuery(static::class);
    }

    public function insert($runValidation = true, $attributes = null) 
    {
        if (!$this->beforeSave(true)) return false;

        $baseAttribute = $this->getBaseAttributes();
        $dirtyAttribute = $this->getDirtyAttributes($attributes);
        
        $baseValues = [];
        $values = $dirtyAttribute;
        foreach ($values as $key => $value)
        {
            if (in_array($key, $baseAttribute))
            {
                $baseValues[$key] = $value;
                unset($values[$key]);
            }
        }
        $collectionClass = static::collectionModel();
        $collectionModel = new $collectionClass($baseValues);
        $collectionModel->{static::typeAttribute()} = $this->typeId;
        if (!$collectionModel->save()) 
        {
            $this->addErrors($collectionModel->errors);
            return false;
        }
        $primaryKeys = $collectionModel::primaryKey();
        foreach ($primaryKeys as $primaryKey)
        {
            $id = $collectionModel->{$primaryKey};
            $this->setAttribute($primaryKey, $id);
            $dirtyAttribute[$primaryKey] = $id;
        }
        
        foreach ($values as $key => $value)
        {
            $fieldClass = static::collectionFieldModel();
            $fieldModel = $fieldClass::find()->andWhere(['handle' => $key])->one();
            if (!$this->insertValueModel($collectionModel, $fieldModel, $value)) return false;
        }
        $changedAttributes = array_fill_keys(array_keys($dirtyAttribute), null);
        $this->setOldAttributes($dirtyAttribute);
        $this->afterSave(true, $changedAttributes);

        return true;
    }

    public function insertValueModel($collection, $field, $value = true)
    {
        $valueClass = static::collectionValueModel();
        $valueModel = new $valueClass([
            static::fkCollectionId()    => $collection->id,
            static::fkFieldId()         => $field->id,
            'value'                     => $value,
        ]);
        return $valueModel->save(false) ? $valueModel->id : false;
    }

    public function updateInternal($attributes = null)
    {
        if (!$this->beforeSave(false)) return false;

        $values = $this->getDirtyAttributes($attributes);
        if (empty($values)) 
        {
            $this->afterSave(false, $values);
            return 0;
        }
        
        $condition = $this->getOldPrimaryKey(true);
        $lock = $this->optimisticLock();
        if ($lock) 
        {
            $values[$lock] = $this->$lock + 1;
            $condition[$lock] = $this->$lock;
        }

        $collectionClass = static::collectionModel();
        $id = $condition[$collectionClass::primaryKey()[0]];
        $collectionModel = $collectionClass::findOne($id);

        list($baseValues, $fieldValues) = $this->attributeSeparator($values);
        if (empty($baseValues)) $baseValues = [static::typeAttribute() => $this->typeId];

        $rows = $collectionClass::updateAll($baseValues, $condition);

        if ($lock !== null && !$rows) throw new StaleObjectException('The object being updated is outdated.');
        
        $valueUpdated = $this->updateValueModels($collectionModel, $fieldValues);

        if (isset($values[$lock])) $this->$lock = $values[$lock];

        $changedAttributes = [];
        foreach ($values as $name => $value) 
        {
            $changedAttributes[$name] = isset($this->_oldAttributes[$name]) ? $this->_oldAttributes[$name] : null;
            $this->_oldAttributes[$name] = $value;
        }
        $this->afterSave(false, $changedAttributes);
        return $rows > 0 || $valueUpdated;
    }

    protected function attributeSeparator($values)
    {
        $baseAttributes = $this->getBaseAttributes();
        $baseValues = [];
        $fieldValues = $values;
        foreach ($values as $key => $value)
        {
            if (in_array($key, $baseAttributes))
            {
                $baseValues[$key] = $value;
                unset($fieldValues[$key]);
            }
        }
        return [$baseValues, $fieldValues];
    }

    public function updateValueModels($collectionModel, array $values)
    {
        $updateStatus = true;
        $fieldClass = static::collectionFieldModel();
        $valueClass = static::collectionValueModel();
        $fieldConditions = [];

        foreach ($values as $key => $value)
        {
            $fieldModel = $fieldClass::find()
                ->andWhere(['handle' => $key])
                ->one();

            $valueModel = $valueClass::find()
                ->andWhere([static::fkFieldId() => $fieldModel->id])
                ->andWhere([static::fkCollectionId() => $collectionModel->id])
                ->one();

            if (!$valueModel) 
            {
                $valueModel = new $valueClass([
                    static::fkFieldId()         => $fieldModel->id,
                    static::fkCollectionId()    => $collectionModel->id,
                ]);
                $valueModel->save();
            }

            $fieldConditions[$valueModel::primaryKey()[0]] = $valueModel->id;
            $fieldConditions[static::fkCollectionId()] = $collectionModel->id;

            if(!$valueClass::updateAll(['value' => $value], $fieldConditions)) $updateStatus = false;
        }
        return $updateStatus;
    }

    public static function getDb() 
    {
        $modelClass = static::collectionModel();
        return $modelClass::getDb();
    }
}