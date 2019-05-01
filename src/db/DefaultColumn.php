<?php
namespace paw\db;

use paw\db\Migration;
use paw\helpers\StringHelper;

trait DefaultColumn
{
    public function init()
    {
        parent::init();
        $this->on(Migration::EVENT_AFTER_TABLE_CREATE, [$this, 'onAfterTableCreate']);
        $this->on(Migration::EVENT_BEFORE_TABLE_DROP, [$this, 'onBeforeTableDrop']);
    }

    public function defaultColumns()
    {
        return [
            'created_ip' => $this->string(36)->defaultValue(null),
            'updated_ip' => $this->string(36)->defaultValue(null),
            'created_by' => $this->integer()->unsigned()->defaultValue(null),
            'created_at' => $this->timestamp()->defaultValue(null),
            'updated_by' => $this->integer()->unsigned()->defaultValue(null),
            'updated_at' => $this->timestamp()->defaultValue(null),
            'is_deleted' => $this->boolean()->defaultValue(false),
            'deleted_by' => $this->integer()->unsigned()->defaultValue(null),
            'deleted_at' => $this->timestamp()->defaultValue(null),
        ];
    }

    public function onAfterTableCreate($event)
    {
        try {
            $matchs = [];
            $yiiTableName = $event->table;
            $tableName = $event->table;
            if (preg_match('/\{\{\%([a-zA-z_]+)\}\}/i', $event->table, $matchs)) {
                list($yiiTableName, $tableName) = $matchs;
            }
            $columns = $event->columns;
            $userFkColumns = ['created_by', 'updated_by', 'deleted_by'];
            foreach ($userFkColumns as $columnName) {
                if (isset($columns[$columnName]) && class_exists(\paw\user\models\User::class)) {
                    $this->addForeignKey(
                        $this->generateDefaultFKName($tableName, $columnName),
                        $yiiTableName, $columnName,
                        \paw\user\models\User::tableName(), 'id',
                        'cascade', 'cascade'
                    );
                }
            }
        } catch (\Exception $ex) {
            if (YII_DEBUG) {
                throw $ex;
            }
        }
    }

    public function onBeforeTableDrop($event)
    {
        try {
            $matchs = [];
            $yiiTableName = $event->table;
            $tableName = $event->table;
            if (preg_match('/\{\{\%([a-zA-z_]+)\}\}/i', $event->table, $matchs)) {
                list($yiiTableName, $tableName) = $matchs;
            }
            $userFkColumns = ['created_by', 'updated_by', 'deleted_by'];
            foreach ($userFkColumns as $columnName) {
                $this->dropForeignKey($this->generateDefaultFKName($tableName, $columnName), $yiiTableName);
            }
        } catch (\Exception $ex) {
            if (YII_DEBUG) {
                throw $ex;
            }
        }
    }

    private function generateDefaultFKName($tableName, $columnName)
    {
        return StringHelper::strtr('fk_{tableName}_{columnName}', compact('tableName', 'columnName'));
    }
}
