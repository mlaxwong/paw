<?php
namespace paw\base;

use Yii;
use yii\helpers\ArrayHelper;

class Module extends \yii\base\Module
{
    public $config = [];
    
    public $theme;

    protected $_config = [];

    protected $_viewPath;

    public function __construct($id, $parent = null, $config = [])
    {
        $this->_config = $config;
        parent::__construct($id, $parent, $config);
    }

    public function init()
    {
        parent::init();
        $this->registerComponents($this->getFullConfig(), Yii::$app->config);
    }

    protected function getFullConfig()
    {
        $fullConfig = ArrayHelper::merge($this->_config, $this->config);
        
        // parse components
        if (isset($this->_config['components']) && isset($this->config['components']))
        {
            unset($fullConfig['components']);
            $fullConfig['components'] = $this->componentMergeParser(
                ArrayHelper::filter($this->config, ["components"]),
                ArrayHelper::filter($this->_config, ["components"])
            )['components'];
        }

        return $fullConfig;
    }

    public function getViewPath()
    {
        if ($this->_viewPath === null) {
            $this->_viewPath = $this->getBasePath() . DIRECTORY_SEPARATOR . 'views';
        } else if (is_callable($this->_viewPath)) {
            $this->_viewPath = call_user_func_array($this->_viewPath, [$this]);
        }
        return $this->_viewPath;
    }

    public function setViewPath($path)
    {
        $this->_viewPath = Yii::getAlias($path);
    }

    protected function registerComponents($config, $appConfig = [])
    {
        $moduleConfig = ArrayHelper::filter($config, ['components']);
        if ($moduleConfig)
        {
            $components = [];
            $moduleConfigComponents = $moduleConfig['components'];

            $moduleConfigBase = [];
            foreach ($moduleConfigComponents as $key => $componentConfig)
            {
                $components[$key] = ArrayHelper::remove($moduleConfigComponents[$key], 'on afterComponentRegister');
                $moduleConfig['components'][$key] = $moduleConfigComponents[$key];

                $moduleConfigBase = ArrayHelper::merge($moduleConfigBase, ArrayHelper::filter($appConfig, ["components.$key"]));
            }

            Yii::configure($this, $this->componentMergeParser($moduleConfigBase, $moduleConfig));
    
            foreach ($components as $key => $afterComponentRegister)
            {
                $component = $this->get($key);
                Yii::$app->set($key, $component);
                if (is_callable($afterComponentRegister)) {
                    call_user_func_array($afterComponentRegister, [$component]);
                }
            }
        }
    }

    protected function componentMergeParser($based, $extra)
    {
        if (isset($based['components']) && isset($extra['components']))
        {
            foreach ($extra['components'] as $componentId => $config)
            {
                if (!isset($based['components'][$componentId])) continue;
                foreach ($config as $key => $value)
                {
                    if (isset($based['components'][$componentId][$key])) {
                        ArrayHelper::remove($based['components'][$componentId], $key);
                    }
                }
            }
        }
        return ArrayHelper::merge($based, $extra);
    }
}