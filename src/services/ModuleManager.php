<?php
namespace paw\services;

use Yii;
use yii\base\BootstrapInterface;
use yii\base\Component;
use yii\helpers\ArrayHelper;

class ModuleManager extends Component implements BootstrapInterface
{
    public $defaultModule = null;

    public $modules = [];

    public function bootstrap($app)
    {
        $this->registerModules();
    }

    protected function registerModules()
    {
        foreach ($this->modules as $moduleId => $moduleConfig) {
            $module = $this->getModuleObject($moduleId, $moduleConfig);
            $urlRules = $this->getModuleUrlRules($module);
            // echo '<pre>';
            // echo htmlspecialchars(print_r($urlRules, true));
            // echo '</pre>';
            $this->registerModule($moduleId, $moduleConfig);
            $this->registerUrlRule($urlRules);
        }
        // die;
    }

    protected function registerModule($moduleId, $moduleConfig)
    {
        Yii::$app->setModule($moduleId, $moduleConfig);
    }

    protected function registerUrlRule($urlRules)
    {
        Yii::$app->urlManager->addRules($urlRules, false);
    }

    protected function getModuleObject($moduleId, $moduleConfig)
    {
        $moduleClass = is_string($moduleConfig) ? $moduleConfig : $moduleConfig['class'];
        return new $moduleClass($moduleId);
    }

    protected function getModuleUrlRules($module)
    {
        $rules = [];
        $moduleId = $module->id;
        $isDefaultModule = $this->defaultModule == $moduleId;
        $urlPrefix = $isDefaultModule ? '' : "$moduleId/";

        $controllersKeys = array_keys($module->controllers);
        $defaultControllerRules = [];
        foreach ($controllersKeys as $controllersKey) {
            if ($controllersKey == 'default') {
                $defaultControllerRules = ArrayHelper::merge($defaultControllerRules, [
                    "$urlPrefix<action>" => "$moduleId/default/<action>",
                    "$urlPrefix<action:()>" => "$moduleId/default/index",
                ]);
                if ($urlPrefix) {
                    $defaultControllerRules = ArrayHelper::merge($defaultControllerRules, [
                        "$moduleId" => "$moduleId/default/index",
                    ]);
                }
            } else {
                $rules = ArrayHelper::merge($rules, [
                    "$urlPrefix<controller:($controllersKey)>/<action>" => "$moduleId/<controller>/<action>",
                    "$urlPrefix<controller:($controllersKey)>" => "$moduleId/<controller>/index",
                    "$urlPrefix<controller:($controllersKey)>/<action:()>" => "$moduleId/<controller>/index",
                ]);
            }
        }

        if ($defaultControllerRules) {
            $rules = ArrayHelper::merge($rules, $defaultControllerRules);
        }

        if ($isDefaultModule) {
            $moduleIds = array_keys($this->modules);
            ArrayHelper::removeValue($moduleIds, $moduleId);
            $pattern = $moduleIds ? ':^((?!' . implode(')(?!', $moduleIds) . '))[\w-\/]+' : '';
            $rules = ArrayHelper::merge($rules, [
                "<action$pattern>" => "$moduleId/default/<action>",
            ]);
        }

        // if ($moduleId == 'client') {
        //     echo '<pre>';
        //     echo htmlspecialchars(print_r($rules, true));
        //     echo '</pre>';die;
        // }

        return $rules;
    }
}
