<?php
namespace paw\events;

use Yii;

class AfterLoginEvent
{
    public static function handleLoginTrack($event)
    {
        $user = Yii::$app->user->identity;
        if ($user instanceof \paw\web\IdentityInterface)
        {
            $loggedAtColumn = $user->getLoggedAtColumn();
            if ($loggedAtColumn)
            {
                $modelClass = get_class($user);
                $primaryKeys = $user->getPrimaryKey(true);
                $conditions = [];
                foreach ($primaryKeys as $key => $value) $conditions[] = "`$key`='$value'";
                $condition = implode(' AND ', $conditions);
                if ($condition) {
                    call_user_func_array([$modelClass, 'updateAll'], [[$loggedAtColumn => new \yii\db\Expression('NOW()')], $condition]);
                }
            }
        }
    }
}