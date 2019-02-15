<?php 
namespace paw\behaviors;

use yii\base\Behavior;
use yii\base\UnknownMethodException;
use yii\base\UnknownPropertyException;

class RelationBehavior extends Behavior
{
    const MANY_TO_MANY  = 'manytomany';
    const MANY_TO_ONE   = 'manytoone';
    const ONE_TO_MANY   = 'onetomany';
    const ONE_TO_ONE    = 'onetoone';

    protected $_relations = 
    [
        'field' => [\paw\records\CollectionField::class, 'hasOne', ['id' => 'id'], []],
    ];

    public function addRelation($class, $mode = self::MANY_TO_MANY, $condition = []) {}

    public function removeRelation($class) {}

    protected function getRelation($class, $mode, $link, $condition = [])
    {
        return $this->owner->{$mode}($class, $link);
    }

    public function hasMethod($name)
    {
        if (parent::hasMethod($name)) return true;
        if ($this->hasRelation(ltrim($name, 'get'))) return true;
        return false;
    }

    // public function hasProperty($name, $checkVars = true)
    // {
    //     die;
    // }

    public function hasRelation($name)
    {
        return isset($this->_relations[$name]); 
    }

    public function __call($name, $params = [])
    {
        try {
            return parent::__call($name, $params);
        } catch (UnknownMethodException $ex) {
            $relationName = ltrim($name, 'get');
            if ($this->hasRelation($relationName)) {
                return call_user_func_array([$this, 'getRelation'], $this->_relations[$relationName]);
                // return $this->owner->hasOne(get_class($this->owner), ['id' => 'id']);
            } else {
                throw $ex;
            }
        }
    }

    public function canGetProperty($name, $checkVars = true)
    {
        return parent::canGetProperty($name, $checkVars) || isset($this->_relations[$name]);
    }

    public function __get($name)
    {
        try {
            return parent::__get($name);
        } catch (UnknownPropertyException $ex) {
            if ($this->hasRelation($name)) {
                return call_user_func_array([$this, 'getRelation'], $this->_relations[$name]);
            } else {
                throw $ex;
            }
        }
    }

    protected function createRelationQuery($class, $link, $multiple)
    {
        $query = $class::find();
        $query->primaryModel = $this->owner;
        $query->link = $link;
        $query->multiple = $multiple;
        return $query;
    }
}