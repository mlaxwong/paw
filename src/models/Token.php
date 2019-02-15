<?php
namespace paw\models;

use Yii;
use yii\db\ActiveRecord;
use yii\db\Expression;
use paw\behaviors\TimestampBehavior;
use paw\behaviors\SerializeBehavior;

class Token extends ActiveRecord
{
    const TOKEN_DEFAULT_DURATION = 1 * 24 * 60 * 60;
    const TOKEN_SECRET_LENGTH = 6;
    const TOKEN_ALGO = 'sha1';

    public $duration = null;

    public function behaviors()
    {
        return [
            ['class' => TimestampBehavior::class],
            [
                'class' => SerializeBehavior::class,
                'attributes' => ['model_primary_key', 'data'],
            ],
        ];
    }

    public static function tableName()
    {
        return '{{%token}}';
    }

    public function rules()
    {
        return [
            [['token_key', 'duration'], 'required'],
            [['type', 'token_key'], 'string'],
            [['secret'], 'string', 'length' => self::TOKEN_SECRET_LENGTH],
            [['duration'], 'integer'],
            [['expire_at'], 'datetime', 'format' => 'php:Y-m-d H:i:s'],
            [['created_at', 'updated_at', 'model_class', 'model_primary_key', 'data'], 'safe'],
        ];
    }

    public function beforeSave($insert)
    {
        if (!parent::beforeSave($insert)) return false;
        if ($this->duration) $this->expire_at = date('Y-m-d H:i:s', time() + $this->duration);
        return true;
    }

    public function verifyData($data)
    {
        ksort($data);
        return $this->token_key == self::generateTokenKey($this->secret, $data);
    }

    public function claim($data)
    {
        if (!$this->verifyData($data)) return false;
        $this->renew(-1);
        return true;
    }

    public function renew($duration = self::TOKEN_DEFAULT_DURATION)
    {
        $this->duration = $duration;
        return $this->save();
    }

    public static function create($model, $type, $data = [], $duration = self::TOKEN_DEFAULT_DURATION)
    {
        $modelClass = get_class($model);
        $modelPrimaryKey = $model->getPrimaryKey(true);
        $secret = Yii::$app->security->generateRandomString(self::TOKEN_SECRET_LENGTH);
        $tokenKey = self::generateTokenKey($secret, $data);

        $token = new self([
            'duration'          => $duration,
            'model_class'       => $modelClass,
            'model_primary_key' => $modelPrimaryKey,
            'type'              => $type,
            'secret'            => $secret,
            'token_key'         => $tokenKey,
            'data'              => $data,
        ]);
        if (!$token->save()) return false;

        return $token;
    }

    public static function claimInstance($model, $type, $tokenKey, $data = [])
    {
        $token = self::getInstance($model, $type, $tokenKey);
        if (!$token) return false;
        return $token->claim($data);
    }

    public static function getInstance($model, $type, $tokenKey, $includeExpired = false)
    {
        $modelClass         = get_class($model);
        $modelPrimaryKey    = $model->getPrimaryKey(true);
        $query = self::find()
            ->andWhere([
                'model_class'       => $modelClass,
                'model_primary_key' => serialize($modelPrimaryKey),
                'type'              => $type,
                'token_key'         => $tokenKey,
            ]);
        if (!$includeExpired) $query->andWhere(['>=', 'expire_at', new Expression('NOW()')]);
        return $query->one();
    }

    public static function generateTokenKey($secret, $data = [], $algo = self::TOKEN_ALGO)
    {
        ksort($data);
        $hash = hash_init($algo, HASH_HMAC, $secret);
        hash_update($hash, serialize($data));
        return hash_final($hash);
    }
}