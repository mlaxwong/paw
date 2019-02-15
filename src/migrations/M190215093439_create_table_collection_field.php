<?php
namespace paw\migrations;

use paws\db\Migration;

class M190215093439_create_table_collection_field extends Migration
{
    public function safeUp()
    {
        $this->createTable('{{%collection_field}}', [
            'id' => $this->primaryKey()->unsigned(),
            'name' => $this->string(256)->notNull(),
            'handle' => $this->string(256)->notNull(),
            'config_class' => $this->string(512)->notNull(),
            'config' => $this->text()->defaultValue(null),
        ]);
    }

    public function safeDown()
    {
        $this->dropTable('{{%collection_field}}');
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "M190215093439_create_table_collection_field cannot be reverted.\n";

        return false;
    }
    */
}