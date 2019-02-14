<?php
namespace paw\commands;

use Yii;
use yii\db\Connection;
use yii\db\Query;
use yii\di\Instance;
use yii\helpers\ArrayHelper;
use yii\helpers\Console;
use yii\console\controllers\BaseMigrateController;

class MigrateController extends BaseMigrateController
{
    const MAX_NAME_LENGTH = 180;
    public $migrationTable = '{{%migration}}';
    
    public $templateFile = '@yii/views/migration.php';
    
    public $generatorTemplateFiles = [
        'create_table' => '@yii/views/createTableMigration.php',
        'drop_table' => '@yii/views/dropTableMigration.php',
        'add_column' => '@yii/views/addColumnMigration.php',
        'drop_column' => '@yii/views/dropColumnMigration.php',
        'create_junction' => '@yii/views/createTableMigration.php',
    ];
    
    public $useTablePrefix = false;
    
    public $fields = [];
    
    public $db = 'db';
    
    public $comment = '';
    public function options($actionID)
    {
        return array_merge(
            parent::options($actionID),
            ['migrationTable', 'db'], // global for all actions
            $actionID === 'create'
                ? ['templateFile', 'fields', 'useTablePrefix', 'comment']
                : []
        );
    }
    public function optionAliases()
    {
        return array_merge(parent::optionAliases(), [
            'C' => 'comment',
            'f' => 'fields',
            'p' => 'migrationPath',
            't' => 'migrationTable',
            'F' => 'templateFile',
            'P' => 'useTablePrefix',
            'c' => 'compact',
        ]);
    }
    public function beforeAction($action)
    {
        if (parent::beforeAction($action)) {
            if ($action->id !== 'create') $this->db = Instance::ensure($this->db, Connection::class);
            return true;
        }
        return false;
    }
    protected function createMigration($class)
    {
        $this->includeMigrationFile($class);
        return Yii::createObject([
            'class' => $class,
            'db' => $this->db,
            'compact' => $this->compact,
        ]);
    }
    protected function getMigrationHistory($limit)
    {
        if ($this->db->schema->getTableSchema($this->migrationTable, true) === null) {
            $this->createMigrationHistoryTable();
        }
        $query = (new Query())
            ->select(['version', 'apply_time'])
            ->from($this->migrationTable)
            ->orderBy(['apply_time' => SORT_DESC, 'version' => SORT_DESC]);
        if (empty($this->migrationNamespaces)) {
            $query->limit($limit);
            $rows = $query->all($this->db);
            $history = ArrayHelper::map($rows, 'version', 'apply_time');
            unset($history[self::BASE_MIGRATION]);
            return $history;
        }
        $rows = $query->all($this->db);
        $history = [];
        foreach ($rows as $key => $row) {
            if ($row['version'] === self::BASE_MIGRATION) {
                continue;
            }
            if (preg_match('/m?(\d{6}_?\d{6})(\D.*)?$/is', $row['version'], $matches)) {
                $time = str_replace('_', '', $matches[1]);
                $row['canonicalVersion'] = $time;
            } else {
                $row['canonicalVersion'] = $row['version'];
            }
            $row['apply_time'] = (int) $row['apply_time'];
            $history[] = $row;
        }
        usort($history, function ($a, $b) {
            if ($a['apply_time'] === $b['apply_time']) {
                if (($compareResult = strcasecmp($b['canonicalVersion'], $a['canonicalVersion'])) !== 0) {
                    return $compareResult;
                }
                return strcasecmp($b['version'], $a['version']);
            }
            return ($a['apply_time'] > $b['apply_time']) ? -1 : +1;
        });
        $history = array_slice($history, 0, $limit);
        $history = ArrayHelper::map($history, 'version', 'apply_time');
        return $history;
    }
    protected function createMigrationHistoryTable()
    {
        $tableName = $this->db->schema->getRawTableName($this->migrationTable);
        $this->stdout("Creating migration history table \"$tableName\"...", Console::FG_YELLOW);
        $this->db->createCommand()->createTable($this->migrationTable, [
            'version' => 'varchar(' . static::MAX_NAME_LENGTH . ') NOT NULL PRIMARY KEY',
            'apply_time' => 'integer',
        ])->execute();
        $this->db->createCommand()->insert($this->migrationTable, [
            'version' => self::BASE_MIGRATION,
            'apply_time' => time(),
        ])->execute();
        $this->stdout("Done.\n", Console::FG_GREEN);
    }
    protected function addMigrationHistory($version)
    {
        $command = $this->db->createCommand();
        $command->insert($this->migrationTable, [
            'version' => $version,
            'apply_time' => time(),
        ])->execute();
    }
    protected function truncateDatabase()
    {
        $db = $this->db;
        $schemas = $db->schema->getTableSchemas();
        // First drop all foreign keys,
        foreach ($schemas as $schema) {
            if ($schema->foreignKeys) {
                foreach ($schema->foreignKeys as $name => $foreignKey) {
                    $db->createCommand()->dropForeignKey($name, $schema->name)->execute();
                    $this->stdout("Foreign key $name dropped.\n");
                }
            }
        }
        // Then drop the tables:
        foreach ($schemas as $schema) {
            $db->createCommand()->dropTable($schema->name)->execute();
            $this->stdout("Table {$schema->name} dropped.\n");
        }
    }
    protected function removeMigrationHistory($version)
    {
        $command = $this->db->createCommand();
        $command->delete($this->migrationTable, [
            'version' => $version,
        ])->execute();
    }
    private $_migrationNameLimit;
    protected function getMigrationNameLimit()
    {
        if ($this->_migrationNameLimit !== null) {
            return $this->_migrationNameLimit;
        }
        if ($this->action->id !== 'create') {
            $tableSchema = $this->db->schema ? $this->db->schema->getTableSchema($this->migrationTable, true) : null;
            if ($tableSchema !== null) {
                return $this->_migrationNameLimit = $tableSchema->columns['version']->size;
            }
        }
        return static::MAX_NAME_LENGTH;
    }
    protected function generateTableName($tableName)
    {
        if (!$this->useTablePrefix) {
            return $tableName;
        }
        return '{{%' . $tableName . '}}';
    }
    protected function addDefaultPrimaryKey(&$fields)
    {
        foreach ($fields as $field) {
            if (false !== strripos($field['decorators'], 'primarykey()')) {
                return;
            }
        }
        array_unshift($fields, ['property' => 'id', 'decorators' => 'primaryKey()']);
    }
}