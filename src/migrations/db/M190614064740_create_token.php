<?php
namespace paw\migrations\db;

use paw\db\Migration;

class M190614064740_create_token extends Migration
{
    use \paw\db\TextTypesTrait;
    use \paw\db\DefaultColumn;

    public function safeUp()
    {
        $this->createTable('{{%token}}', [
            'id' => $this->primaryKey()->unsigned(),
            'expire_at' => $this->timestamp()->defaultValue(null),
            'model_class' => $this->string()->defaultValue(null),
            'model_primary_key' => $this->string()->defaultValue(null),
            'public_key' => $this->string()->defaultValue(null),
            'secret_key' => $this->string()->defaultValue(null),
            'data' => $this->longText()->defaultValue(null),
        ]);
    }

    public function safeDown()
    {
        $this->dropTable('{{%token}}');
    }

    /*
// Use up()/down() to run migration code without a transaction.
public function up()
{

}

public function down()
{
echo "M190614064740_create_token cannot be reverted.\n";

return false;
}
 */
}
