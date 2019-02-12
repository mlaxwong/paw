<?php
namespace paw\web;

use Yii;

class View extends \yii\web\View
{
    public $themeClass = \paw\web\Theme::class;

    public $defaultExtension = 'twig';

    public function init()
    {
        $moduleTheme = $this->getModuleTheme();
        if ($moduleTheme) {
            $this->theme = $moduleTheme;
        }

        if (isset($this->theme['asset']) && !isset($this->theme['class'])) {
            $this->theme['class'] = $this->themeClass;
        }

        if ($this->theme)
        {
            $this->theme = Yii::createObject($this->theme);
            $this->theme->registerAsset($this);
        }
        // 'pathMap' => [
        //     '@app/views' => '@app/themes/goldbridge/views/',
        //     '@common/modules' => '@app/themes/goldbridge/views/modules',
        //     '@frontend/modules' => '@app/themes/goldbridge/views/modules',
        // ],

        parent::init();
    }

    protected function getModuleTheme()
    {
        $controller = Yii::$app->controller;

        if (!$controller) return null;

        $module = $controller->module;

        return isset($module->theme) ? $module->theme : null;
    }
}