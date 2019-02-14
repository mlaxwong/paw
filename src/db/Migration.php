<?php 
namespace paw\db;

class Migration extends \yii\db\Migration
{
    public $db = 'db';
    
    public function init()
    {
        parent::init();
    }

    public function createTable($table, $columns, $options = null)
    {
        if ($options === null) $options = $this->getDefaultTableOptions();
        return parent::createTable($table, $columns, $options);
    }
    
    protected function getDefaultTableOptions()
    {
        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            // http://stackoverflow.com/questions/766809/whats-the-difference-between-utf8-general-ci-and-utf8-unicode-ci
            $tableOptions = 'CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE=InnoDB';
        }
        return $tableOptions;
    }
}