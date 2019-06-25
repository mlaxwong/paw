<?php
namespace paw\behaviors;

use paw\helpers\Json;
use paw\models\Token;
use yii\base\Behavior;
use yii\db\ActiveRecord;

class TokenBehavior extends Behavior
{
    public $data = [];
    public $duration = 60 * 5;

    public function events()
    {
        return [
            ActiveRecord::EVENT_AFTER_INSERT => 'afterInsert',
            ActiveRecord::EVENT_AFTER_UPDATE => 'afterUpdate',
            ActiveRecord::EVENT_AFTER_DELETE => 'afterDelete',
        ];
    }

    public function afterInsert($event)
    {
        $this->createToken();
        // $owner = $this->owner;
        // $token = new Token([
        //     'model_class' => get_class($owner),
        //     'model_primary_key' => $owner->id,
        //     'duration' => $this->duration,
        //     'data' => $this->getTokenData(),
        // ]);

        // if (!$token->save()) {
        //     throw new \Exception(Yii::t('app', 'Unable to create token'));
        // }
    }

    public function afterUpdate($event)
    {
        $regenerateKey = false;
        $token = $this->getOwnerToken();
        $changedAttributes = $event->changedAttributes;

        $data = $token->data;
        if (Json::isJson($data)) {
            $data = Json::decode($data);
        }
        $data = is_array($data) ? $data : [];
        $dataKeys = array_keys($data);

        if ($changedAttributes && $dataKeys) {
            foreach ($changedAttributes as $key => $value) {
                if (in_array($key, $dataKeys)) {
                    $regenerateKey = true;
                    break;
                }
            }
        }

        if ($regenerateKey) {
            $token->data = $this->getTokenData();
            $token->generatePublicKey(true);
            $token->save();
        }
    }

    public function afterDelete($event)
    {
        $token = $this->owner->token;
        if ($token) {
            $token->delete();
        }
    }

    public function getToken()
    {
        $owner = $this->owner;
        return $owner->hasOne(Token::class, ['model_primary_key' => 'id'])->andOnCondition([Token::tableName() . '.model_class' => get_class($owner)]);
    }

    protected function getTokenData()
    {
        $owner = $this->owner;
        $data = $this->data;
        if (!is_array($data)) {
            return [];
        }
        $tokenData = [];
        foreach ($data as $key => $value) {
            if (is_callable($value)) {
                $tokenData[$key] = call_user_func_array($value, [$owner, $this]);
            } elseif (is_integer($key)) {
                $tokenData[$value] = $owner->{$value};
            } else {
                $tokenData[$key] = $owner->{$value};
            }
        }
        return $tokenData;
    }

    public function getIsTokenExpired()
    {
        $token = $this->owner->token;
        return $token ? $token->isExpired : false;
    }

    public function renewToken($duration = null)
    {
        $token = $this->getOwnerToken(true);
        $duration = $duration === null ? $this->duration : $duration;
        return $token->renew($duration);
    }

    protected function getOwnerToken($outdated = false)
    {
        $token = $this->owner->token;
        return $token ? $token : $this->createToken($outdated);
    }

    protected function createToken($outdated = false)
    {
        $owner = $this->owner;
        $token = new Token([
            'model_class' => get_class($owner),
            'model_primary_key' => $owner->id,
            'duration' => $outdated ? -1 : $this->duration,
            'data' => $this->getTokenData(),
        ]);
        $token->scenario = Token::SCENARIO_CREATE;

        if (!$token->save()) {
            throw new \Exception(Yii::t('app', 'Unable to create token'));
        }
        return $token;
    }
}
