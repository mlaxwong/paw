<?php
namespace paw\migrations\db;

use nterms\mailqueue\MailQueue;
use Yii;
use yii\db\Migration;

class M190215095551_add_sent_time_index extends Migration
{
    public function up()
    {
        $this->createIndex('IX_sent_time', Yii::$app->get(MailQueue::NAME)->table, 'sent_time');
    }
    public function down()
    {
        $this->dropIndex('IX_sent_time', Yii::$app->get(MailQueue::NAME)->table);
    }
}
