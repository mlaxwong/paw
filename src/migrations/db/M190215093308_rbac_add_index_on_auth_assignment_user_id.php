<?php
namespace paw\migrations\db;

use Yii;
use yii\base\InvalidConfigException;
use yii\rbac\DbManager;
use paw\db\Migration;

class M190215093308_rbac_add_index_on_auth_assignment_user_id extends Migration
{
    public $column = 'user_id';
    public $index = 'auth_assignment_user_id_idx';

    protected function getAuthManager()
    {
        $authManager = Yii::$app->getAuthManager();
        if (!$authManager instanceof DbManager) {
            throw new InvalidConfigException('You should configure "authManager" component to use database before executing this migration.');
        }

        return $authManager;
    }

    public function up()
    {
        $authManager = $this->getAuthManager();
        $this->createIndex($this->index, $authManager->assignmentTable, $this->column);
    }

    public function down()
    {
        $authManager = $this->getAuthManager();
        $this->dropIndex($this->index, $authManager->assignmentTable);
    }
}
