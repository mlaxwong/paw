<?php
namespace paw\db;;

use yii\db\ActiveQueryTrait;
use yii\db\ActiveRelationTrait;
use yii\db\ActiveQuery;
use yii\db\ActiveQueryInterface;
use yii\db\Query;
use yii\db\Expression;
use yii\db\ActiveRecordInterface;

class CollectionQuery extends ActiveQuery implements ActiveQueryInterface
{
    // use ActiveQueryTrait;
    // use ActiveRelationTrait;

    protected $_type;

    public function type($type)
    {
        $modelClass = $this->modelClass;
        $typeClass = $modelClass::collectionTypeModel();

        if ($type instanceof $typeClass) {
            $this->_type = $type;
        } elseif (is_integer($type)) {
            $this->_type = $typeClass::findOne($type);
        } elseif (is_string($type)) {
            $this->_type = $typeClass::find()
                ->andWhere(['handle' => $type])
                ->one();
        }
        return $this;
    }

    public function prepare($builder)
    {
        if (!empty($this->joinWith)) 
        {
            $this->buildJoinWith();
            $this->joinWith = null;
        }

        if (empty($this->from)) $this->from = [$this->getPrimaryTableName()];

        $this->select = $this->getSelect($this->select);
        
        if ($this->primaryModel === null) {
            $query = Query::create($this);
        } else {
            $where = $this->where;

            if ($this->via instanceof self) {
                $viaModels = $this->via->findJunctionRows([$this->primaryModel]);
                $this->filterByModels($viaModels);
            } elseif (is_array($this->via)) {
                list($viaName, $viaQuery) = $this->via;
                if ($viaQuery->multiple) {
                    $viaModels = $viaQuery->all();
                    $this->primaryModel->populateRelation($viaName, $viaModels);
                } else {
                    $model = $viaQuery->one();
                    $this->primaryModel->populateRelation($viaName, $model);
                    $viaModels = $model === null ? [] : [$model];
                }
                $this->filterByModels($viaModels);
            } else {
                $this->filterByModels([$this->primaryModel]);
            }

            $query = Query::create($this);
            $this->where = $where;
        }

        if (!empty($this->on)) $query->andWhere($this->on);

        return $query;
    }

    protected function getSelect($select = null)
    {
        if (empty($select) && !empty($this->join)) 
        {
            list(, $alias) = $this->getTableNameAndAlias();
            $select = ["$alias.*"];
        }
        if (empty($select)) $select = $this->getDefaultSelect();
        return $select;
    }

    protected function getDefaultSelect()
    {
        $selects = [];
        $modelClass = $this->modelClass;
        $collectionClass = $modelClass::collectionModel();
        $valueClass = $modelClass::collectionValueModel();
        $baseAttributes = (new $modelClass)->getBaseAttributes(); 
        foreach ($baseAttributes as $select) $selects[] = $collectionClass::tableName() . '.' . $select;

        $type = $this->getType();
        if ($type)
        {
            foreach ($type->fields as $field)
            {
                $selects[] = new Expression("(SELECT `value` FROM " . $valueClass::tableName() . " WHERE `" . $modelClass::fkFieldId() . "` = '" . $field->id . "' AND `" . $modelClass::fkCollectionId() . "` = " . $collectionClass::tableName() . ".id) AS `" . $field->handle . "`");
            }
        }
        return $selects;
    }

    protected function createModels($rows)
    {
        $type = $this->getType();
        if ($this->asArray) {
            return $rows;
        } else {
            $models = [];
            /* @var $class ActiveRecord */
            $class = $this->modelClass;
            foreach ($rows as $row) {
                $model = $class::instantiate($row);
                $modelClass = get_class($model);
                // if ($type === null)
                // {
                    $fieldClass = $model::collectionFieldModel();
                    $valueClass = $model::collectionValueModel();
                    foreach ($model->getCustomAttributes() as $attribute)
                    {
                        $fieldModel = $fieldClass::find()->andWhere(['handle' => $attribute])->one();
                        $valueModel = $valueClass::find()
                            ->andWhere([$model->fkCollectionId() => $row[$model::primaryKey()[0]]])
                            ->andWhere([$model->fkFieldId() => $fieldModel->id])
                            ->one();
                        if ($valueModel) $row[$attribute] = $valueModel->value;
                    }
                // }
                $modelClass::populateRecord($model, $row);
                $models[] = $model;
            }
            return $models;
        }
    }

    protected function getPrimaryTableName()
    {
        $modelClass = $this->modelClass;
        $collectionClass = $modelClass::collectionModel();
        return $collectionClass::tableName();
    }

    public function getType()
    {
        return $this->_type;
    }

    private function findJunctionRows($primaryModels)
    {
        if (empty($primaryModels)) {
            return [];
        }
        $this->filterByModels($primaryModels);
        /* @var $primaryModel ActiveRecord */
        $primaryModel = reset($primaryModels);
        if (!$primaryModel instanceof ActiveRecordInterface) {
            // when primaryModels are array of arrays (asArray case)
            $primaryModel = $this->modelClass;
        }

        return $this->asArray()->all($primaryModel::getDb());
    }

    private function filterByModels($models)
    {
        $attributes = array_keys($this->link);

        $attributes = $this->prefixKeyColumns($attributes);

        $values = [];
        if (count($attributes) === 1) {
            // single key
            $attribute = reset($this->link);
            foreach ($models as $model) {
                if (($value = $model[$attribute]) !== null) {
                    if (is_array($value)) {
                        $values = array_merge($values, $value);
                    } else {
                        $values[] = $value;
                    }
                }
            }
            if (empty($values)) {
                $this->emulateExecution();
            }
        } else {
            // composite keys

            // ensure keys of $this->link are prefixed the same way as $attributes
            $prefixedLink = array_combine(
                $attributes,
                array_values($this->link)
            );
            foreach ($models as $model) {
                $v = [];
                foreach ($prefixedLink as $attribute => $link) {
                    $v[$attribute] = $model[$link];
                }
                $values[] = $v;
                if (empty($v)) {
                    $this->emulateExecution();
                }
            }
        }
        $this->andWhere(['in', $attributes, array_unique($values, SORT_REGULAR)]);
    }

    private function prefixKeyColumns($attributes)
    {
        if ($this instanceof ActiveQuery && (!empty($this->join) || !empty($this->joinWith))) {
            if (empty($this->from)) {
                /* @var $modelClass ActiveRecord */
                $modelClass = $this->modelClass;
                $alias = $modelClass::tableName();
            } else {
                foreach ($this->from as $alias => $table) {
                    if (!is_string($alias)) {
                        $alias = $table;
                    }
                    break;
                }
            }
            if (isset($alias)) {
                foreach ($attributes as $i => $attribute) {
                    $attributes[$i] = "$alias.$attribute";
                }
            }
        }

        return $attributes;
    }
}  