<?php
namespace paw\migrations\db;

use paw\db\Migration;

class M190215093310_create_table_user extends Migration
{
    public function safeUp()
    {
        $this->createTable('{{%user}}', [
            'id' => $this->primaryKey()->unsigned(),
            'username' => $this->string()->notNull()->unique(),
            'auth_key' => $this->string(32)->notNull(),
            'password_hash' => $this->string()->notNull(),
            'email' => $this->string()->notNull()->unique(),
            'name' => $this->string(126)->defaultValue(NULL),
            'created_at' => $this->timestamp()->defaultValue(NULL),
            'updated_at' => $this->timestamp()->defaultValue(NULL),
            'logged_at' => $this->timestamp()->defaultValue(NULL),
        ]);

        $this->insert(
            '{{%user}}', 
            [
                'id'            => 1, 
                'username'      => 'developer', 
                'auth_key'      => 'NrhetzCJL9wRQemdpHT4GL3zyvZmAuTc', 
                'password_hash' => '$2y$13$TDBeAM/CC8Xpf7WHvgG4bODk1y9Z0YONhI9lzI6wyA90NSy8BBnju', 
                'email'         => 'mlaxwong@gmail.com', 
                'name'         => 'Mlax Wong', 
                'created_at'    => new \yii\db\Expression('NOW()'), 
                'updated_at'    => new \yii\db\Expression('NOW()'),
            ]
        );
    }

    public function safeDown()
    {
        $this->dropTable('{{%user}}');
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "M190215093310_create_table_user cannot be reverted.\n";

        return false;
    }
    */
}