<?php
namespace paw\web;

interface IdentityInterface extends \yii\web\IdentityInterface
{
    public function getLoggedAtColumn();
}