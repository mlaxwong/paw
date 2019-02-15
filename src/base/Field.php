<?php 
namespace paw\base;

use yii\base\Component;
use yii\helpers\FileHelper;
use yii\helpers\StringHelper;

abstract class Field extends Component
{
    abstract static function displayName();

    abstract function getInputHtml();

    public static function getFieldTypeDirectory()
    {
        return dirname(__DIR__) . '/fields';
    }

    public static function getFieldTypeNamespace()
    {
        return 'paw\\fields';
    }

    public static function getFieldTypes()
    {
        $fieldTypes = [];
        $fieldTypeDirectory = self::getFieldTypeDirectory();
        $fieldTypeNamespace = self::getFieldTypeNamespace();
        $files = FileHelper::findFiles($fieldTypeDirectory, ['*.php']);
        foreach ($files as $file)
        {
            $fieldType = StringHelper::basename($file, '.php');
            $fieldClass = $fieldTypeNamespace . '\\' . $fieldType;
            $fieldTypes[$fieldClass] = $fieldClass::displayName();
        }
        return $fieldTypes;
    }
}