<?php
namespace paw\migrations\rbac;

use Yii;
use paw\db\Migration;
use paw\rbac\Role;
use paw\rbac\Permission;

class M190311163501_init extends Migration
{
    public function safeUp()
    {
        $authManager = Paws::$app->authManager;

        // reset
        $authManager->removeAll();

        // permission 
        $developer = $authManager->createRole(Role::ROLE_DEVELOPER);
		$authManager->add($developer);

		$admin = $authManager->createRole(Role::ROLE_ADMIN);
		$authManager->add($admin);
        $authManager->addChild($developer, $admin);

        $user = $authManager->createRole(Role::ROLE_USER);
		$authManager->add($user);
        $authManager->addChild($admin, $user);

        $guest = $authManager->createRole(Role::ROLE_GUEST);
		$authManager->add($guest);
        $authManager->addChild($user, $guest);

        $authManager->assign($developer, 1);
    }

    public function safeDown()
    {

    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "M190311163501_init cannot be reverted.\n";

        return false;
    }
    */
}