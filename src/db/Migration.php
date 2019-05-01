<?php
namespace paw\db;

use paw\db\TableMigrationEvent;
use yii\helpers\ArrayHelper;

class Migration extends \yii\db\Migration
{
    const FUNC_DEFAULT_COLUMNS = 'defaultColumns';

    const EVENT_AFTER_TABLE_CREATE = 'afterTableCreate';
    const EVENT_BEFORE_TABLE_DROP = 'beforeTableDrop';

    public $db = 'db';

    public function createTable($table, $columns, $options = null)
    {
        if ($options === null) {
            $options = $this->getDefaultTableOptions();
        }

        if (method_exists($this, self::FUNC_DEFAULT_COLUMNS)) {
            $defaultColumns = call_user_func_array([$this, self::FUNC_DEFAULT_COLUMNS], [$table, $columns, $options]);
            foreach ($defaultColumns as $columnName => $column) {
                if (isset($columns[$columnName])) {
                    unset($defaultColumns[$columnName]);
                }
            }
            $columns = ArrayHelper::merge($columns, $defaultColumns);
        }

        $createTable = parent::createTable($table, $columns, $options);
        $this->afterTableCreate($table, $columns, $options);
        return $createTable;
    }

    public function dropTable($table)
    {
        $this->beforeTableDrop($table);
        $dropTable = parent::dropTable($table);
        return $dropTable;
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

    public function afterTableCreate($table, $columns, $options)
    {
        $event = new TableMigrationEvent;
        $event->table = $table;
        $event->columns = $columns;
        $event->options = $options;
        $this->trigger(self::EVENT_AFTER_TABLE_CREATE, $event);
    }

    public function beforeTableDrop($table)
    {
        $event = new TableMigrationEvent;
        $event->table = $table;
        $this->trigger(self::EVENT_BEFORE_TABLE_DROP, $event);
    }
}
