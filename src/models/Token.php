<?php
namespace paw\models;

use paw\behaviors\SerializeBehavior;
use paw\behaviors\TimestampBehavior;
use paw\helpers\Json;
use Yii;
use yii\behaviors\BlameableBehavior;
use yii\db\ActiveRecord;

class Token extends ActiveRecord
{
    const SCENARIO_CREATE = 'scenario_create';

    const TOKEN_DEFAULT_DURATION = 1 * 24 * 60 * 60;
    const TOKEN_ALGO = 'sha1';

    public $duration = null;

    public function behaviors()
    {
        return [
            TimestampBehavior::class,
            BlameableBehavior::class,
            [
                'class' => SerializeBehavior::class,
                'attributes' => ['model_primary_key', 'data'],
                'serializeMethod' => SerializeBehavior::METHOD_JSON,
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
            [['duration'], 'required', 'on' => self::SCENARIO_CREATE],
            [['public_key', 'secret_key'], 'string'],
            [['duration'], 'integer'],
            [['expire_at'], 'datetime', 'format' => 'php:Y-m-d H:i:s'],
            [['created_at', 'updated_at', 'model_class', 'model_primary_key', 'data'], 'safe'],
        ];
    }

    public function beforeSave($insert)
    {
        if (!parent::beforeSave($insert)) {
            return false;
        }

        if ($this->duration) {
            $this->expire_at = new \yii\db\Expression('DATE_ADD(NOW(), INTERVAL ' . $this->duration . ' SECOND)');
            // $this->expire_at = date('Y-m-d H:i:s', time() + $this->duration);
        }

        if ($insert) {
            // generate token public & secrect key
            $this->generatePublicKey();
        }

        $data = $this->getFormattedData();
        $this->data = $this->serializeData($data);

        return true;
    }

    public function validData($public_key, array $data = [])
    {
        $data = $this->getFormattedData($data);
        $serializeData = $this->serializeData($data);
        return $public_key == self::getPublicKey($this->secret_key, $serializeData);
    }

    public function generatePublicKey($regenerate = false)
    {
        if ($this->public_key === null || $regenerate) {
            $algo = self::TOKEN_ALGO;
            $secretKey = Yii::$app->security->generateRandomString();
            $data = $this->getFormattedData();
            $serializeData = $this->serializeData($data);
            $this->secret_key = $secretKey; // set new secret key
            $this->public_key = self::getPublicKey($secretKey, $serializeData);
        }
    }

    protected function getFormattedData($data = null)
    {
        $data = $data === null ? $this->data : $data;
        if (!is_array($data)) {
            $data = Json::isJson($data) ? Json::decode($data) : [];
        }
        ksort($data);
        return $data;
    }

    protected function serializeData(array $data)
    {
        return Json::encode($data);
    }

    public function getIsExpired()
    {
        return self::find()
            ->andWhere(['id' => $this->id])
            ->andWhere(['<', 'expire_at', new \yii\db\Expression('NOW()')])
            ->exists();
    }

    public function renew($duration = null)
    {
        $this->generatePublicKey(true);
        $this->duration = $duration;
        return $this->save();
    }

    protected static function getPublicKey($secretKey, $serializeData = null)
    {
        $algo = 'sha1';
        $hash = hash_init($algo, HASH_HMAC, $secretKey);
        hash_update($hash, $serializeData);
        return hash_final($hash);
    }
}
