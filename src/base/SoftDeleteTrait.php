<?php
namespace paw\base;

trait SoftDeleteTrait
{
    public static function extraSoftDeleteQueryBehaviors()
    {
        return [];
    }

    public static function find()
    {
        $find = new \yii\db\ActiveQuery(get_called_class());
        $find->attachBehavior('softDelete', \paw\behaviors\SoftDeleteQueryBehavior::class);
        foreach (self::extraSoftDeleteQueryBehaviors() as $key => $behavior) {
            $find->attachBehavior($key, $behavior);
        }
        $find->notDeleted();
        return $find;
    }

    public static function findWithTrashed()
    {
        $find = new \yii\db\ActiveQuery(get_called_class());
        $find->attachBehavior('softDelete', \paw\behaviors\SoftDeleteQueryBehavior::class);
        foreach (self::extraSoftDeleteQueryBehaviors() as $key => $behavior) {
            $find->attachBehavior($key, $behavior);
        }
        return $find;
    }

    public static function findTrashed()
    {
        $find = new \yii\db\ActiveQuery(get_called_class());
        $find->attachBehavior('softDelete', \paw\behaviors\SoftDeleteQueryBehavior::class);
        foreach (self::extraSoftDeleteQueryBehaviors() as $key => $behavior) {
            $find->attachBehavior($key, $behavior);
        }
        $find->deleted();
        return $find;
    }
}
