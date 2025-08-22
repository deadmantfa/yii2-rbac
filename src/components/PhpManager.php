<?php

declare(strict_types=1);

namespace deadmantfa\yii2\rbac\components;

use yii\rbac\Permission;

class PhpManager extends \yii\rbac\PhpManager
{
    use AutoMasterItemTrait;

    /**
     * @var Permission|null
     */
    protected $_masterPermission;
}
