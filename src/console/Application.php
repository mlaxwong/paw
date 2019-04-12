<?php
namespace paw\console;

class Application extends \yii\console\Application
{
    use \paw\base\ApplicationTrait;

    public function __construct($config = [])
    {
        parent::__construct($config);
        $this->config = $config;
    }
}