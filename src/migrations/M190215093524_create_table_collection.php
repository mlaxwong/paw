<?php
namespace paw\migrations;

use paws\db\Migration;

class M190215093524_create_table_collection extends Migration
{
    public function safeUp()
    {
        $this->createTable('{{%collection}}', [
            'id' => $this->primaryKey()->unsigned(),
            'collection_type_id' => $this->integer(11)->unsigned(),
            // 'name' => $this->string(256)->notNull(),
            // 'handle' => $this->string(256)->notNull(),
            'created_at' => $this->timestamp()->defaultValue(null),
            'updated_at' => $this->timestamp()->defaultValue(null),
        ]);

        $this->addForeignKey(
            'fk_collection_collection_type_id',
            '{{%collection}}', 'collection_type_id',
            '{{%collection_type}}', 'id',
            'cascade', 'cascade'
        );
    }

    public function safeDown()
    {
        $this->dropForeignKey('fk_collection_collection_type_id', '{{%collection}}');
        $this->dropTable('{{%collection}}');
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "M190215093524_create_table_collection cannot be reverted.\n";

        return false;
    }
    */
}