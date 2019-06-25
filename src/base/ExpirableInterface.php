<?php
namespace paw\base;

interface ExpirableInterface
{
    public function getIsExpired();

    public function renew($duration = null);

    public function expire();
}
