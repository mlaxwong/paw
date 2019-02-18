<?php
namespace paw\services;

use yii\base\Component;
use yii\helpers\Inflector;

class Resource extends Component
{
    public $namespaces = [];

    public function get($handle)
    {
        foreach ($this->namespaces as $namespace)
        {
            $class = '\\' . $namespace . '\\' . Inflector::classify($handle);
            if (class_exists($class) && $class::getInstance() instanceof \paw\db\Resource) 
            {
                return $class::getInstance();
                break;
            }
        }
        return null;
    }
}