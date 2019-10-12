<?php
namespace paw\behaviors;

use yii\base\Behavior;
use yii\db\ActiveRecord;

class AttributeTimestampBehavior extends Behavior
{
    public $attribute;
    public $targetAttribute;

    public function events()
    {
        return [
            ActiveRecord::EVENT_AFTER_INSERT => 'afterSave',
            ActiveRecord::EVENT_AFTER_UPDATE => 'afterSave',
        ];
    }

    public function afterSave($event)
    {
        if ($this->attribute && $this->targetAttribute) {
            $attribute = $this->attribute;
            $targetAttribute = $this->targetAttribute;
            $changedAttributes = $event->changedAttributes;
            if (array_key_exists($targetAttribute, $changedAttributes)) {
                $owner = $this->owner;
                $id = $owner->id;
                $ownerClass = get_class($owner);
                call_user_func_array([$ownerClass, 'updateAll'], [[$attribute => new \yii\db\Expression('NOW()')], ['id' => $id]]);
            }
        }
    }
}