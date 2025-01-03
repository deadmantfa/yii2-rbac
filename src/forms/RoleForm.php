<?php

declare(strict_types=1);

namespace deadmantfa\yii2\rbac\forms;

use deadmantfa\yii2\rbac\models\Permission;
use deadmantfa\yii2\rbac\models\Role;
use Exception;
use Yii;
use yii\helpers\ArrayHelper;
use yii\rbac\Item;


class RoleForm extends ItemForm
{
    /**
     * @var string[]
     */
    public array $childRoles = [];

    /**
     * @var string[]
     */
    public array $allowPermissions = [];

    /**
     * @var string[]
     */
    protected array $inheritPermissions = [];

    /**
     * @var Role
     */
    protected Role $role;

    /**
     * @inheritdoc
     */
    public function init(): void
    {
        $this->type = Item::TYPE_ROLE;
        $this->role = new Role();
    }

    /**
     * @inheritdoc
     * @return array
     */
    public function rules(): array
    {
        return ArrayHelper::merge(parent::rules(), [
            [['childRoles', 'allowPermissions'], 'each', 'rule' => ['string']],
        ]);
    }

    /**
     * @inheritdoc
     * @return array
     */
    public function attributeLabels(): array
    {
        return array_merge(parent::attributeLabels(), [
            'childRoles' => 'Inherit Roles'
        ]);
    }

    /**
     * @inheritdoc
     * @return array
     */
    public function attributeHints(): array
    {
        return [
            'childRoles' => 'You can inherit other roles to have the same permissions as other roles. <br> 
				Allowed Permissions box will be updated with inherited permissions once you save changes.',
        ];
    }

    /**
     * @inheritdoc
     */
    public function uniqueItemName(string $attribute, array $params, mixed $validator): bool
    {
        $name = $this->$attribute;
        if (Role::find($name)) {
            $this->addError($attribute, 'Role with the same name is already exists.');
            return false;
        }
        return true;
    }

    /**
     * Setter for $role
     * @param Role $role
     */
    public function setRole(Role $role): void
    {
        $this->role = $role;
        $this->load((array)$role->getItem(), '');

        $childRoles = Yii::$app->authManager->getChildRoles($this->name);
        $this->childRoles = array_diff(array_keys($childRoles), [$this->name]);

        $this->allowPermissions = Role::getPermissionsRecursive($this->name);
        $this->inheritPermissions = $this->getInheritPermissions();
    }

    /**
     * Get array of inherit permissions from child Roles
     *
     * @return string[]
     */
    public function getInheritPermissions(): array
    {
        $herited = [];
        if (!empty($this->childRoles)) {
            foreach ($this->childRoles as $roleName) {
                $permissions = Yii::$app->authManager->getPermissionsByRole($roleName);
                $herited = array_merge(
                    $herited,
                    array_keys($permissions)
                );
            }

            $herited = array_unique($herited);
            $herited = array_combine($herited, $herited);
        }
        return $herited;
    }

    /**
     * Main form process method
     *
     * @return bool
     * @throws Exception
     */
    public function save(): bool
    {
        if (!$this->validate()) {
            return false;
        }

        if (!$item = $this->role->getItem()) {
            $item = Role::create($this->name, $this->description);
        }

        $item->description = $this->description;
        $updated = Yii::$app->authManager->update($item->name, $item);

        // clean relations
        Yii::$app->authManager->removeChildren($item);

        // set relations from input
        Role::addChilds($item, $this->childRoles, Role::TYPE_ROLE);

        $allow = $this->getCleanAllowPermissions();
        Role::addChilds($item, $allow, Role::TYPE_PERMISSION);

        return $updated;
    }

    /**
     * Clean allowPermissions from inherit Permissions and recursive childs, which should not be added to RBAC relations
     *
     * @return string[]
     */
    public function getCleanAllowPermissions(): array
    {
        $allowPermissions = array_diff($this->allowPermissions, $this->inheritPermissions);
        list($parents, $children) = Permission::getParentChildMap($allowPermissions);
        unset($children);
        $cleanPermissions = array_combine($allowPermissions, $allowPermissions);
        foreach ($parents as $child => $childParents) {
            if (isset($cleanPermissions[$child])
                && array_intersect($childParents, $allowPermissions)
            ) {
                unset($cleanPermissions[$child]);
            }
        }

        return $cleanPermissions;
    }

    /**
     * List of available deny permissions
     * (all permissions without allow/inherit permissions)
     *
     * @return string[]
     */
    public function getDenyPermissions(): array
    {
        $permissions = Permission::getList();
        return array_diff($permissions, $this->allowPermissions, $this->inheritPermissions);
    }

    /**
     * Prepare linear tree array with depth and weight parameters
     *
     * @param string[] $permissions
     * @param bool $missingParents Include parents, which are not present in the tree in "parent" attribute
     *
     * @return array
     */
    public function getLinearTree(array $permissions, bool $missingParents = true): array
    {
        if (empty($permissions)) {
            return [];
        }

        list($parents, $children) = Permission::getParentChildMap($missingParents ? null : $permissions);

        return $this->buildLinearTree($permissions, $permissions, $children, $parents);
    }

    /**
     * Recursive function to go over tree and sort/move items correctly.
     *
     * @param array $array
     * @param array $items
     * @param array $children
     * @param null $parents
     * @param int $depth
     *
     * @return array
     */
    protected function buildLinearTree(array $array, array &$items, array &$children, &$parents, int $depth = 0): array
    {
        static $position;

        $tree = [];
        foreach ($array as $item) {
            if (!isset($items[$item])) {
                continue;
            }

            $tree[$item] = [
                'id' => $item,
                'text' => $item,
                'parent' => isset($parents[$item]) ? end($parents[$item]) : '#',
                'depth' => $depth,
                'order' => (int)$position++,
            ];
            unset($items[$item]);

            if (!empty($children[$item])) {
                $tree += $this->buildLinearTree($children[$item], $items, $children, $parents, $depth + 1);
            }
        }

        return $tree;
    }

}
