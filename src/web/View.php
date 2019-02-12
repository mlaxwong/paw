<?php
namespace paw\web;

class View extends \yii\web\View
{
    public function init()
    {
        parent::init();
        if ($this->theme)
        {
            if ($this->theme->asset) $this->theme->registerAsset($this);
        }
        // 'pathMap' => [
        //     '@app/views' => '@app/themes/goldbridge/views/',
        //     '@common/modules' => '@app/themes/goldbridge/views/modules',
        //     '@frontend/modules' => '@app/themes/goldbridge/views/modules',
        // ],
    }
}