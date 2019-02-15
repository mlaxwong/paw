<?php
namespace paw\behaviors;

use yii\behaviors\AttributeBehavior;
use yii\db\BaseActiveRecord;

class IpBehavior extends AttributeBehavior 
{    
    public $createdIpAttribute = 'created_ip';
    public $updatedIpAttribute = 'updated_ip';
    public $value;
    
    public function init() 
    {
        parent::init();
        if (empty($this->attributes)) 
        {
            $this->attributes = [
                BaseActiveRecord::EVENT_BEFORE_INSERT => [$this->createdIpAttribute, $this->updatedIpAttribute],
                BaseActiveRecord::EVENT_BEFORE_UPDATE => $this->updatedIpAttribute,
            ];
        }
    }
    
    
    protected function getValue($event) 
    {
        if (is_string($this->value)) {
            return $this->value;
        } else {
            return $this->value !== null ? call_user_func($this->value, $event) : \Yii::$app->request->userIp;
        }
    }
    
    public function setIp($attribute) 
    {
        $this->owner->updateAttributes(array_fill_keys((array) $attribute, $this->getValue(null)));
    }
}