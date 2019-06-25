<?php
namespace paw\behaviors;

use paw\models\Token;
use yii\base\Behavior;

class TokenQueryBehavior extends Behavior
{
    public function expired()
    {
        return $this->owner->joinWith('token')->andWhere(['<', Token::tableName() . '.expire_at', new \yii\db\Expression('NOW()')]);
    }

    public function notExpired()
    {
        return $this->owner->joinWith('token')->andWhere(['>=', Token::tableName() . '.expire_at', new \yii\db\Expression('NOW()')]);
    }
}
