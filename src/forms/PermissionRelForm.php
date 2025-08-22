<?php

declare(strict_types=1);

namespace deadmantfa\yii2\rbac\forms;

use deadmantfa\yii2\rbac\models\Permission;
use Yii;
use yii\base\Exception;
use yii\base\Model;
use yii\rbac\Item;

class PermissionRelForm extends Model
{
    const SCENARIO_ADDROLE = 'role';
    const SCENARIO_ADDPARENT = 'parent';
    const SCENARIO_ADDCHILD = 'child';


    public $names;

    protected Permission $permission;

    /**
     * @inheritdoc
     */
    public function rules(): array
    {
        return [
            ['names', 'required'],
            ['names', 'each', 'rule' => [
                'match', 'pattern' => ItemForm::getNamePattern(),
            ]],
            ['scenario', 'in',
                'range' => static::getScenarios(),
                'on' => static::getScenarios(),
            ],
            ['scenario', 'safe'],
        ];
    }

    /**
     * List of available scenarios
     */
    public static function getScenarios(): array
    {
        return [
            static::SCENARIO_ADDROLE,
            static::SCENARIO_ADDPARENT,
            static::SCENARIO_ADDCHILD,
        ];
    }

    /**
     * Setter for $permission
     */
    public function setPermission(Permission $permission): void
    {
        $this->permission = $permission;
    }

    /**
     * ADD form process method
     *
     * @throws Exception
     */
    public function addRelations(): bool|int
    {
        if (!$this->validate()) {
            return false;
        }

        $added = 0;
        foreach ($this->names as $name) {
            [$parent, $child] = $this->getParentChild($name);

            if ($parent && $child && !Yii::$app->authManager->hasChild($parent, $child)) {
                Yii::$app->authManager->addChild($parent, $child);
                $added++;
            }
        }
        return $added;
    }

    /**
     * Helper to define correct parent and child
     *
     */
    public function getParentChild(string $itemName): array
    {
        $item = (static::SCENARIO_ADDROLE === $this->scenario) ?
            Yii::$app->authManager->getRole($itemName) :
            Yii::$app->authManager->getPermission($itemName);

        $parent = (static::SCENARIO_ADDCHILD === $this->scenario) ? $this->permission->getItem() : $item;
        $child = (static::SCENARIO_ADDCHILD === $this->scenario) ? $item : $this->permission->getItem();

        return [$parent, $child];
    }

    /**
     * REMOVE form process method
     *
     */
    public function removeRelation(string $itemName): bool
    {
        [$parent, $child] = $this->getParentChild($itemName);
        if (!$parent || !$child || !Yii::$app->authManager->hasChild($parent, $child)) {
            return false;
        }
        return Yii::$app->authManager->removeChild($parent, $child);
    }

    public function validateNoHierarchyLoop(string $newItemName): bool
    {
        $authManager = Yii::$app->authManager;
        $currentItem = $authManager->getPermission($this->permission->name) ?? $authManager->getRole($this->permission->name);

        if (!$currentItem) {
            return false; // Current item is invalid
        }

        $descendants = $this->getAllDescendants($currentItem);

        return !in_array($newItemName, $descendants, true);
    }

    /**
     * Recursively fetch all descendants of an item.
     */
    private function getAllDescendants(Item $item): array
    {
        $authManager = Yii::$app->authManager;
        $children = $authManager->getChildren($item->name);
        $descendants = array_keys($children);

        foreach ($children as $child) {
            $descendants = array_merge($descendants, $this->getAllDescendants($child));
        }

        return $descendants;
    }
}
