<?php
namespace paw\rbac;

use yii\base\Component;

class Role extends Component
{
    const ROLE_DEVELOPER    = 'developer';
    const ROLE_ADMIN        = 'admin';
    const ROLE_USER         = 'user';
    const ROLE_GUEST        = 'guest';
}