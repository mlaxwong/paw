<?php
namespace paw\base;

use Yii;

class Module extends \yii\base\Module
{
    public $theme;

    protected $_viewPath;

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
}