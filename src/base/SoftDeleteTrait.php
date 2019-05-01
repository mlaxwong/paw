<?php
namespace paw\base;

trait SoftDeleteTrait
{
    public static function find()
    {
        $find = new \yii\db\ActiveQuery(get_called_class());
        $find->attachBehavior('softDelete', \paw\behaviors\SoftDeleteQueryBehavior::class);
        $find->notDeleted();
        return $find;
    }

    public static function findWithTrashed()
    {
        $find = new \yii\db\ActiveQuery(get_called_class());
        $find->attachBehavior('softDelete', \paw\behaviors\SoftDeleteQueryBehavior::class);
        return $find;
    }

    public static function findTrashed()
    {
        $find = new \yii\db\ActiveQuery(get_called_class());
        $find->attachBehavior('softDelete', \paw\behaviors\SoftDeleteQueryBehavior::class);
        $find->deleted();
        return $find;
    }
}
