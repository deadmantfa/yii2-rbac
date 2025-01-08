<?php

declare(strict_types=1);

namespace deadmantfa\yii2\rbac\components;


use deadmantfa\yii2\rbac\models\Item;
use yii\rbac\Permission;

trait AutoMasterItemTrait
{
    /**
     * @inheritdoc
     */
    protected function addItem($item): bool
    {
        if (parent::addItem($item)) {
            // add master permission as parent to any new item of Permission type.
            if ($item instanceof Permission
                && Item::PERMISSION_MASTER !== $item->name
                && $master = $this->getMasterPermission()
            ) {
                $this->addChild($master, $item);
            }

            // add everything permission as parent
            return true;
        }
        return false;
    }

    /**
     * Find master permission object.
     */
    protected function getMasterPermission(): ?\yii\rbac\Item
    {
        if (property_exists($this, '_masterPermission')) {
            if (empty($this->_masterPermission)) {
                $this->_masterPermission = $this->getItem(Item::PERMISSION_MASTER);
            }

            return $this->_masterPermission;
        } else {
            return $this->getItem(Item::PERMISSION_MASTER);
        }
    }
}
