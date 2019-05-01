<?php
namespace paw\behaviors;

use Yii;
use yii\base\Behavior;
use yii\db\BaseActiveRecord;

class DatetimeBehavior extends Behavior
{
    public $attributes = [];

    public $format = 'Y-m-d H:i:s';

    protected $preparedForInsert = false;

    public function events()
    {
        return [
            BaseActiveRecord::EVENT_BEFORE_VALIDATE => 'prepareForInsert',
            BaseActiveRecord::EVENT_AFTER_VALIDATE => 'afterValidate',
            BaseActiveRecord::EVENT_BEFORE_INSERT => 'prepareForInsert',
        ];
    }

    public function afterValidate($event)
    {
        if ($event->sender->errors) {
            $this->prepareForGetter($event);
        }
    }

    public function prepareForGetter($event)
    {
        if ($this->preparedForInsert) {
            foreach ($this->attributes as $attribute) {
                $owner = $this->owner;
                $owner->{$attribute} = $this->getGetterValue($event, $owner->{$attribute});
            }
            $this->preparedForInsert = false;
        }
    }

    public function prepareForInsert($event)
    {
        if (!$this->preparedForInsert) {
            foreach ($this->attributes as $attribute) {
                $owner = $this->owner;
                $owner->{$attribute} = $this->getInsertValue($event, $owner->{$attribute});
            }
            $this->preparedForInsert = true;
        }
    }

    protected function getInsertValue($event, $value)
    {
        if (!$value) {
            return null;
        }

        try {
            $datetime = new \DateTime($value, new \DateTimeZone(Yii::$app->timeZone));
            return $datetime->format($this->format);
        } catch (\Exception $ex) {
            return null;
        }
    }

    protected function getGetterValue($event, $value)
    {
        if (!$value) {
            return null;
        }

        try {
            $datetime = new \DateTime($value, new \DateTimeZone(Yii::$app->timeZone));
            return $datetime->format(\DateTime::W3C);
        } catch (\Exception $ex) {
            return null;
        }
    }
}
