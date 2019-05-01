<?php
namespace paw\behaviors;

use Yii;
use yii\base\Behavior;
use yii\base\InvalidConfigException;
use yii\base\ModelEvent;
use yii\db\ActiveRecord;
use yii\db\BaseActiveRecord;
use yii\db\StaleObjectException;

class SoftDeleteBehavior extends Behavior
{
    const EVENT_BEFORE_SOFT_DELETE = 'beforeSoftDelete';

    const EVENT_AFTER_SOFT_DELETE = 'afterSoftDelete';

    const EVENT_BEFORE_RESTORE = 'beforeRestore';

    const EVENT_AFTER_RESTORE = 'afterRestore';

    public $softDeleteAttributeValues = [
        'is_deleted' => true,
    ];

    public $softDeleteByAttribute = 'deleted_by';

    public $softDeleteAtAttribute = 'deleted_at';

    public $softDeleteTimestampAttribute = 'deleted_at';

    public $restoreAttributeValues;

    public $invokeDeleteEvents = true;

    public $allowDeleteCallback;

    public $deleteFallbackException = 'yii\db\IntegrityException';

    private $_replaceRegularDelete = false;

    public function getReplaceRegularDelete()
    {
        return $this->_replaceRegularDelete;
    }

    public function setReplaceRegularDelete($replaceRegularDelete)
    {
        $this->_replaceRegularDelete = $replaceRegularDelete;
        if (is_object($this->owner)) {
            $owner = $this->owner;
            $this->detach();
            $this->attach($owner);
        }
    }

    public function softDelete()
    {
        if ($this->isDeleteAllowed()) {
            return $this->owner->delete();
        }
        $softDeleteCallback = function () {
            if ($this->invokeDeleteEvents && !$this->owner->beforeDelete()) {
                return false;
            }
            $result = $this->softDeleteInternal();
            if ($this->invokeDeleteEvents) {
                $this->owner->afterDelete();
            }
            return $result;
        };
        if (!$this->isTransactional(ActiveRecord::OP_DELETE) && !$this->isTransactional(ActiveRecord::OP_UPDATE)) {
            return call_user_func($softDeleteCallback);
        }
        $transaction = $this->beginTransaction();
        try {
            $result = call_user_func($softDeleteCallback);
            if ($result === false) {
                $transaction->rollBack();
            } else {
                $transaction->commit();
            }
            return $result;
        } catch (\Exception $exception) {
            // PHP < 7.0
        } catch (\Throwable $exception) {
            // PHP >= 7.0
        }
        $transaction->rollBack();
        throw $exception;
    }

    protected function softDeleteInternal()
    {
        $result = false;
        if ($this->beforeSoftDelete()) {
            $attributes = $this->owner->getDirtyAttributes();
            foreach ($this->softDeleteAttributeValues as $attribute => $value) {
                if (!is_scalar($value) && is_callable($value)) {
                    $value = call_user_func($value, $this->owner);
                }
                $attributes[$attribute] = $value;
            }
            if ($this->softDeleteTimestampAttribute) {
                $attributes[$this->softDeleteTimestampAttribute] = new \yii\db\Expression('NOW()');
            }
            if (Yii::$app->has('user') && $this->softDeleteByAttribute) {
                if (!Yii::$app->user->isGuest) {
                    $attributes[$this->softDeleteByAttribute] = Yii::$app->user->id;
                }

            }
            $result = $this->updateAttributes($attributes);
            $this->afterSoftDelete();
        }
        return $result;
    }

    public function beforeSoftDelete()
    {
        if (method_exists($this->owner, 'beforeSoftDelete')) {
            if (!$this->owner->beforeSoftDelete()) {
                return false;
            }
        }
        $event = new ModelEvent();
        $this->owner->trigger(self::EVENT_BEFORE_SOFT_DELETE, $event);
        return $event->isValid;
    }

    public function afterSoftDelete()
    {
        if (method_exists($this->owner, 'afterSoftDelete')) {
            $this->owner->afterSoftDelete();
        }
        $this->owner->trigger(self::EVENT_AFTER_SOFT_DELETE);
    }

    protected function isDeleteAllowed()
    {
        if ($this->allowDeleteCallback === null) {
            return false;
        }
        return call_user_func($this->allowDeleteCallback, $this->owner);
    }

    public function restore()
    {
        $restoreCallback = function () {
            $result = false;
            if ($this->beforeRestore()) {
                $result = $this->restoreInternal();
                $this->afterRestore();
            }
            return $result;
        };
        if (!$this->isTransactional(ActiveRecord::OP_UPDATE)) {
            return call_user_func($restoreCallback);
        }
        $transaction = $this->beginTransaction();
        try {
            $result = call_user_func($restoreCallback);
            if ($result === false) {
                $transaction->rollBack();
            } else {
                $transaction->commit();
            }
            return $result;
        } catch (\Exception $exception) {
            // PHP < 7.0
        } catch (\Throwable $exception) {
            // PHP >= 7.0
        }
        $transaction->rollBack();
        throw $exception;
    }

    protected function restoreInternal()
    {
        $restoreAttributeValues = $this->restoreAttributeValues;
        if ($restoreAttributeValues === null) {
            foreach ($this->softDeleteAttributeValues as $name => $value) {
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
                    throw new InvalidConfigException('Unable to automatically determine restore attribute values, "' . get_class($this) . '::$restoreAttributeValues" should be explicitly set.');
                }
                $restoreAttributeValues[$name] = $restoreValue;
            }
        }
        $attributes = $this->owner->getDirtyAttributes();
        foreach ($restoreAttributeValues as $attribute => $value) {
            if (!is_scalar($value) && is_callable($value)) {
                $value = call_user_func($value, $this->owner);
            }
            $attributes[$attribute] = $value;
        }
        if ($this->softDeleteTimestampAttribute) {
            $attributes[$this->softDeleteTimestampAttribute] = null;
        }
        if ($this->softDeleteByAttribute) {
            $attributes[$this->softDeleteByAttribute] = null;
        }
        return $this->updateAttributes($attributes);
    }

    public function beforeRestore()
    {
        if (method_exists($this->owner, 'beforeRestore')) {
            if (!$this->owner->beforeRestore()) {
                return false;
            }
        }
        $event = new ModelEvent();
        $this->owner->trigger(self::EVENT_BEFORE_RESTORE, $event);
        return $event->isValid;
    }

    public function afterRestore()
    {
        if (method_exists($this->owner, 'afterRestore')) {
            $this->owner->afterRestore();
        }
        $this->owner->trigger(self::EVENT_AFTER_RESTORE);
    }

    public function safeDelete()
    {
        try {
            $transaction = $this->beginTransaction();
            $result = $this->owner->delete();
            if (isset($transaction)) {
                $transaction->commit();
            }
            return $result;
        } catch (\Exception $exception) {
            // PHP < 7.0
        } catch (\Throwable $exception) {
            // PHP >= 7.0
        }
        if (isset($transaction)) {
            $transaction->rollback();
        }
        $fallbackExceptionClass = $this->deleteFallbackException;
        if ($exception instanceof $fallbackExceptionClass) {
            return $this->softDeleteInternal();
        }
        throw $exception;
    }

    private function isTransactional($operation)
    {
        if (!$this->owner->hasMethod('isTransactional')) {
            return false;
        }
        return $this->owner->isTransactional($operation);
    }

    private function beginTransaction()
    {
        $db = $this->owner->getDb();
        if ($db->hasMethod('beginTransaction')) {
            return $db->beginTransaction();
        }
        return null;
    }

    private function updateAttributes(array $attributes)
    {
        $owner = $this->owner;
        $lock = $owner->optimisticLock();
        if ($lock === null) {
            return $owner->updateAttributes($attributes);
        }
        $condition = $owner->getOldPrimaryKey(true);
        $attributes[$lock] = $owner->{$lock}+1;
        $condition[$lock] = $owner->{$lock};
        $rows = $owner->updateAll($attributes, $condition);
        if (!$rows) {
            throw new StaleObjectException('The object being updated is outdated.');
        }
        foreach ($attributes as $name => $value) {
            $owner->{$name} = $value;
            $owner->setOldAttribute($name, $value);
        }
        return $rows;
    }

    public function events()
    {
        if ($this->getReplaceRegularDelete()) {
            return [
                BaseActiveRecord::EVENT_BEFORE_DELETE => 'beforeDelete',
            ];
        }
        return [];
    }

    public function beforeDelete($event)
    {
        if (!$this->isDeleteAllowed()) {
            $this->softDeleteInternal();
            $event->isValid = false;
        }
    }
}
