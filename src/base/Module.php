<?php
namespace paw\base;

use Yii;
use yii\helpers\ArrayHelper;
use yii\helpers\FileHelper;
use yii\helpers\Inflector;

class Module extends \yii\base\Module
{
    public $config = [];

    public $theme;

    protected $_config = [];

    protected $_viewPath;

    protected $_controllerPath;

    protected $_controllers = null;

    public function __construct($id, $parent = null, $config = [])
    {
        $this->_config = $config;
        parent::__construct($id, $parent, $config);
    }

    public function init()
    {
        parent::init();
        $this->registerComponents($this->getFullConfig(), Yii::$app ? Yii::$app->config : []);
    }

    public function getControllerPath()
    {
        if ($this->_controllerPath === null) {
            $this->_controllerPath = $this->getBasePath() . DIRECTORY_SEPARATOR . 'controllers';
        } else if (is_callable($this->_controllerPath)) {
            $this->_controllerPath = call_user_func_array($this->_controllerPath, [$this]);
        }
        return $this->_controllerPath;
    }

    public function getControllers()
    {
        if ($this->_controllers === null) {
            $controllerNamespace = $this->controllerNamespace;
            $controllerPath = $this->getControllerPath();
            $files = FileHelper::findFiles($controllerPath, ['only' => ['*Controller.php']]);
            $this->_controllers = ArrayHelper::map($files, function ($file) {
                $basename = basename($file, 'Controller.php');
                return Inflector::camel2id($basename);
            }, function ($file) use ($controllerNamespace) {
                $basename = basename($file, '.php');
                $class = $controllerNamespace . '\\' . $basename;
                $id = strtolower(basename($file, 'Controller.php'));
                $controller = new $class($id, $this);
                $actions = [];
                $methods = get_class_methods($class);
                if ($methods) {
                    $actions = array_filter($methods, function ($methodName) {
                        return preg_match('/^action[A-Z]([a-zA-Z]+)?/i', $methodName) && $methodName != 'actions';
                    });
                    array_walk($actions, function (&$item, $key) {
                        $item = strtolower($item);
                        $item = preg_replace('/^action/i', '', $item);
                    });
                }
                $actions = ArrayHelper::merge($actions, array_keys($controller->actions()));
                return compact('id', 'class', 'file', 'actions');
            });
        }
        return $this->_controllers;
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

    protected function getFullConfig()
    {
        if (isset($this->_config['components'])) {
            $components = $this->_config['components'];
            array_walk($components, function (&$item, $key) {
                $item = is_callable($item) ? call_user_func_array($item, [$this]) : $item;
            });
            $this->_config['components'] = $components;
        }

        $fullConfig = ArrayHelper::merge($this->_config, $this->config);

        // parse components
        if (isset($this->_config['components']) && isset($this->config['components'])) {
            unset($fullConfig['components']);
            $fullConfig['components'] = $this->componentMergeParser(
                ArrayHelper::filter($this->config, ["components"]),
                ArrayHelper::filter($this->_config, ["components"])
            )['components'];
        }

        return $fullConfig;
    }

    protected function registerComponents($config, $appConfig = [])
    {
        $moduleConfig = ArrayHelper::filter($config, ['components']);
        if ($moduleConfig) {
            $components = [];
            $moduleConfigComponents = $moduleConfig['components'];

            $moduleConfigBase = [];
            foreach ($moduleConfigComponents as $key => $componentConfig) {
                $components[$key] = ArrayHelper::remove($moduleConfigComponents[$key], 'on afterComponentRegister');
                $moduleConfig['components'][$key] = $moduleConfigComponents[$key];

                $moduleConfigBase = ArrayHelper::merge($moduleConfigBase, ArrayHelper::filter($appConfig, ["components.$key"]));
            }

            Yii::configure($this, $this->componentMergeParser($moduleConfigBase, $moduleConfig));

            foreach ($components as $key => $afterComponentRegister) {
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
        if (isset($based['components']) && isset($extra['components'])) {
            foreach ($extra['components'] as $componentId => $config) {
                if (!isset($based['components'][$componentId])) {
                    continue;
                }

                foreach ($config as $key => $value) {
                    if (isset($based['components'][$componentId][$key])) {
                        ArrayHelper::remove($based['components'][$componentId], $key);
                    }
                }
            }
        }
        return ArrayHelper::merge($based, $extra);
    }
}
