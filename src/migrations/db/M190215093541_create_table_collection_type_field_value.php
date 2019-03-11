<?php
namespace paw\migrations\db;

use paw\db\Migration;

class M190215093541_create_table_collection_type_field_value extends Migration
{
    public function safeUp()
    {
        $this->createTable('{{%collection_value}}', [
            'id' => $this->primaryKey()->unsigned(),
            'collection_id' => $this->integer(11)->unsigned(),
            'collection_field_id' => $this->integer(11)->unsigned(),
            'value' => $this->text()->defaultValue(null),
        ]);

        $this->addForeignKey(
            'fk_collection_value_collection_id',
            '{{%collection_value}}', 'collection_id',
            '{{%collection}}', 'id',
            'cascade', 'cascade'
        );

        $this->addForeignKey(
            'fk_collection_value_collection_field_id',
            '{{%collection_value}}', 'collection_field_id',
            '{{%collection_field}}', 'id',
            'cascade', 'cascade'
        );
    }

    public function safeDown()
    {
        $this->dropForeignKey('fk_collection_value_collection_field_id', '{{%collection_value}}');
        $this->dropForeignKey('fk_collection_value_collection_id', '{{%collection_value}}');
        $this->dropTable('{{%collection_value}}');
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "M190215093541_create_table_collection_type_field_value cannot be reverted.\n";

        return false;
    }
    */
}