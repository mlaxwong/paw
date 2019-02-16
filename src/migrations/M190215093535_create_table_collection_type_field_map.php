<?php
namespace paw\migrations;

use paw\db\Migration;

class M190215093535_create_table_collection_type_field_map extends Migration
{
    public function safeUp()
    {
        $this->createTable('{{%collection_type_field_map}}', [
            'collection_type_id' => $this->integer(11)->unsigned(),
            'collection_field_id' => $this->integer(11)->unsigned(),
        ]);

        $this->addForeignKey(
            'fk_collection_type_field_map_collection_type_id', 
            '{{%collection_type_field_map}}', 'collection_type_id',
            '{{%collection_type}}', 'id',
            'cascade', 'cascade'
        );

        $this->addForeignKey(
            'fk_collection_type_field_map_collection_field_id', 
            '{{%collection_type_field_map}}', 'collection_field_id',
            '{{%collection_field}}', 'id',
            'cascade', 'cascade'
        );
    }

    public function safeDown()
    {
        $this->dropForeignKey('fk_collection_type_field_map_collection_field_id', '{{%collection_type_field_map}}');
        $this->dropForeignKey('fk_collection_type_field_map_collection_type_id', '{{%collection_type_field_map}}');
        $this->dropTable('{{%collection_type_field_map}}');
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "M190215093535_create_table_collection_type_field_map cannot be reverted.\n";

        return false;
    }
    */
}