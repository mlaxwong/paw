<?php
namespace paw\db;

use yii\base\Event;

class TableMigrationEvent extends Event
{
    public $table;
    public $columns;
    public $options = [];
}
