<?php
namespace paw\behaviors;

use yii\db\Expression;

class TimestampBehavior extends \yii\behaviors\TimestampBehavior
{
    protected function getValue($event)
    {
        if ($this->value === null) {
            return new Expression('NOW()');
        }
        return parent::getValue($event);
    }
}