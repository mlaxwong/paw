<?php
namespace paw\behaviors;

use yii\base\Behavior;
use yii\base\InvalidConfigException;

class SoftDeleteQueryBehavior extends Behavior
{
    private $_deletedCondition;

    private $_notDeletedCondition;

    public $showDeletedByDefault = true;

    public function getDeletedCondition()
    {
        if ($this->_deletedCondition === null) {
            $this->_deletedCondition = $this->defaultDeletedCondition();
        }
        return $this->_deletedCondition;
    }

    public function setDeletedCondition($deletedCondition)
    {
        $this->_deletedCondition = $deletedCondition;
    }

    public function getNotDeletedCondition()
    {
        if ($this->_notDeletedCondition === null) {
            $this->_notDeletedCondition = $this->defaultNotDeletedCondition();
        }
        return $this->_notDeletedCondition;
    }

    public function setNotDeletedCondition($notDeletedCondition)
    {
        $this->_notDeletedCondition = $notDeletedCondition;
    }

    public function deleted()
    {
        return $this->addFilterCondition($this->getDeletedCondition());
    }

    public function notDeleted()
    {
        return $this->addFilterCondition($this->getNotDeletedCondition());
    }

    public function filterDeleted($deleted)
    {
        if ($deleted === '' || $deleted === null || $deleted === []) {
            return $this->notDeleted();
        }
        if ((int) $deleted) {
            return $this->deleted();
        }
        return $this->owner;
    }

    protected function addFilterCondition($condition)
    {
        $condition = $this->normalizeFilterCondition($condition);
        if (method_exists($this->owner, 'andOnCondition')) {
            return $this->owner->andOnCondition($condition);
        }
        return $this->owner->andWhere($condition);
    }

    protected function defaultDeletedCondition()
    {
        $modelInstance = $this->getModelInstance();
        $condition = [];
        foreach ($modelInstance->softDeleteAttributeValues as $attribute => $value) {
            if (!is_scalar($value) && is_callable($value)) {
                $value = call_user_func($value, $modelInstance);
            }
            $condition[$attribute] = $value;
        }
        return $condition;
    }

    protected function defaultNotDeletedCondition()
    {
        $modelInstance = $this->getModelInstance();
        $condition = [];
        if ($modelInstance->restoreAttributeValues === null) {
            foreach ($modelInstance->softDeleteAttributeValues as $attribute => $value) {
                if (is_bool($value)) {
                    $restoreValue = !$value;
                } elseif (is_int($value)) {
                    if ($value === 1) {
                        $restoreValue = 0;
                    } elseif ($value === 0) {
                        $restoreValue = 1;
                    } else {
                        $restoreValue = $value + 1;
                    }
                } elseif (!is_scalar($value) && is_callable($value)) {
                    $restoreValue = null;
                } else {
                    throw new InvalidConfigException('Unable to automatically determine not delete condition, "' . get_class($this) . '::$notDeletedCondition" should be explicitly set.');
                }
                $condition[$attribute] = $restoreValue;
            }
        } else {
            foreach ($modelInstance->restoreAttributeValues as $attribute => $value) {
                if (!is_scalar($value) && is_callable($value)) {
                    $value = call_user_func($value, $modelInstance);
                }
                $condition[$attribute] = $value;
            }
        }
        return $condition;
    }

    protected function getModelInstance()
    {
        return call_user_func([$this->owner->modelClass, 'instance']);
    }

    protected function normalizeFilterCondition($condition)
    {
        if (method_exists($this->owner, 'getTablesUsedInFrom')) {
            $fromTables = $this->owner->getTablesUsedInFrom();
            $alias = array_keys($fromTables)[0];
            foreach ($condition as $attribute => $value) {
                if (is_numeric($attribute) || strpos($attribute, '.') !== false) {
                    continue;
                }
                unset($condition[$attribute]);
                if (strpos($attribute, '[[') === false) {
                    $attribute = '[[' . $attribute . ']]';
                }
                $attribute = $alias . '.' . $attribute;
                $condition[$attribute] = $value;
            }
        }
        return $condition;
    }
}
