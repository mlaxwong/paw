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
            $this->registerCustomUrlManagerRule($moduleConfig);
        }
        // die;
    }

    protected function registerModule($moduleId, $moduleConfig)
    {
        Yii::$app->setModule($moduleId, $moduleConfig);
    }

    protected function registerCustomUrlManagerRule($moduleConfig)
    {
        if (isset($moduleConfig['components']['urlManager'])) {
            if (isset($moduleConfig['components']['urlManager']['rules'])) {
                $this->registerUrlRule($moduleConfig['components']['urlManager']['rules']);
            }
        }
    }

    protected function registerUrlRule($urlRules)
    {
        Yii::$app->urlManager->addRules($urlRules, false);
    }

    protected function getModuleObject($moduleId, $moduleConfig)
    {
        $moduleClass = is_string($moduleConfig) ? $moduleConfig : $moduleConfig['class'];
        $module = new $moduleClass($moduleId, null, ['pauseInit' => true]);
        if (isset($moduleConfig['modules'])) {
            $submodules = [];
            foreach ($moduleConfig['modules'] as $submoduleId => $submoduleConfig) {
                $submodules[] = $this->getModuleObject($submoduleId, $submoduleConfig);
            }
            $module->modules = $submodules;
        }
        return $module;
    }

    protected function getModuleUrlRules($module, $parentUrlPrefix = null)
    {
        $rules = [];
        $moduleId = $module->id;
        $isDefaultModule = $this->defaultModule == $moduleId;
        $urlPrefix = $parentUrlPrefix . ($isDefaultModule ? '' : "$moduleId/");

        $controllersKeys = array_keys($module->controllers);
        $defaultControllerRules = [];
        foreach ($controllersKeys as $controllersKey) {
            if ($controllersKey == 'default') {
                $defaultControllerRules = ArrayHelper::merge($defaultControllerRules, [
                    "$urlPrefix<action>" => "$parentUrlPrefix$moduleId/default/<action>",
                    "$urlPrefix<action>/<blank:()>" => "$parentUrlPrefix$moduleId/default/<action>",
                    "$urlPrefix<action:()>" => "$parentUrlPrefix$moduleId/default/index",
                ]);
                if ($urlPrefix) {
                    $defaultControllerRules = ArrayHelper::merge($defaultControllerRules, [
                        "$parentUrlPrefix$moduleId" => "$parentUrlPrefix$moduleId/default/index",
                    ]);
                }
            } else {
                $rules = ArrayHelper::merge($rules, [
                    "$urlPrefix<controller:($controllersKey)>/<action>" => "$parentUrlPrefix$moduleId/<controller>/<action>",
                    "$urlPrefix<controller:($controllersKey)>" => "$parentUrlPrefix$moduleId/<controller>/index",
                    "$urlPrefix<controller:($controllersKey)>/<action:()>" => "$parentUrlPrefix$moduleId/<controller>/index",
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
                "<action$pattern>" => "$parentUrlPrefix$moduleId/default/<action>",
            ]);
        }

        if ($module->modules) {
            foreach ($module->modules as $submodule) {
                $subrules = $this->getModuleUrlRules($submodule, $moduleId . '/');
                $rules = ArrayHelper::merge($subrules, $rules);
            }
        }

        // if ($moduleId == 'client') {
        //     print_r($module->modules);die;

        //     echo '<pre>';
        //     echo htmlspecialchars(print_r($rules, true));
        //     echo '</pre>';die;
        // }
        return $rules;
    }
}
