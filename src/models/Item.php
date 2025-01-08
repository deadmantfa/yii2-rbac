<?php

declare(strict_types=1);

namespace deadmantfa\yii2\rbac\models;

use Yii;
use yii\base\Exception;
use yii\rbac\Item as RbacItem;

class Item
{
    const TYPE_ROLE = 1;
    const TYPE_PERMISSION = 2;

    const ROLE_GUEST = 'Guest';
    const ROLE_AUTHENTICATED = 'Authenticated';
    const ROLE_ADMIN = 'Administrator';
    const ROLE_MASTER = 'Master';

    const PERMISSION_ADMINISTER = 'administer';
    const PERMISSION_MASTER = '*';

    /**
     * @param string[] $childNames
     * @throws Exception
     */
    public static function addChilds(RbacItem $parent, array $childNames, int $type = RbacItem::TYPE_PERMISSION): bool
    {
        if ($childNames === []) return false;

        $auth = Yii::$app->authManager;

        foreach ($childNames as $childName) {
            $item = (RbacItem::TYPE_ROLE === $type) ?
                $auth->getRole($childName) :
                $auth->getPermission($childName);

            if ($item && !$auth->hasChild($parent, $item)) {
                $auth->addChild($parent, $item);
            }
        }

        return true;
    }

}
