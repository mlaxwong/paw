<?php
namespace paw\migrations\db;

use nterms\mailqueue\MailQueue;
use Yii;
use yii\db\Migration;

/**
 * Handles adding swift_message to table `mail_queue`.
 */
class M190215095549_add_swift_message_column_to_mail_queue_table extends Migration
{
    /**
     * @inheritdoc
     */
    public function up()
    {
        $this->addColumn(Yii::$app->get(MailQueue::NAME)->table, 'swift_message', 'text');
    }

    /**
     * @inheritdoc
     */
    public function down()
    {
        $this->dropColumn(Yii::$app->get(MailQueue::NAME)->table, 'swift_message');
    }
}
