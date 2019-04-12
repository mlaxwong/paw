<?php
namespace paw\web;

class Application extends \yii\web\Application
{
    use \paw\base\ApplicationTrait;

    public function __construct($config = [])
    {
        parent::__construct($config);
        $this->config = $config;
    }
}