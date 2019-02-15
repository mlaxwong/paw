<?php
namespace paw\helpers;

class Json extends \yii\helpers\Json
{
    public static function isJson($string)
    {
        if (!is_string($string)) return false;
        
        json_decode($string);
        return (json_last_error() == JSON_ERROR_NONE);
    }
}