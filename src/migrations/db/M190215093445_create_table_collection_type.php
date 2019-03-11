<?php
namespace paw\migrations\db;

use paw\db\Migration;

class M190215093445_create_table_collection_type extends Migration
{
    public function safeUp()
    {
        $this->createTable('{{%collection_type}}', [
            'id' => $this->primaryKey()->unsigned(),
            'name' => $this->string(256)->notNull(),
            'handle' => $this->string(256)->notNull(),
            'mode' => $this->string(64)->notNull(),
        ]);
    }

    public function safeDown()
    {
        $this->dropTable('{{%collection_type}}');
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "M190215093445_create_table_collection_type cannot be reverted.\n";

        return false;
    }
    */
}