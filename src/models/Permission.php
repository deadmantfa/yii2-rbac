<?php

declare(strict_types=1);

namespace deadmantfa\yii2\rbac\models;

use Yii;
use yii\base\Exception;
use yii\helpers\ArrayHelper;
use yii\rbac\Permission as RbacPermission;

class Permission extends Item
{
    /**
     * @var RbacPermission[] Cached permissions.
     */
    public static array $itemsCache = [];

    /**
     * @var array Cached parent-child relations.
     */
    private static array $parentsCache = [];

    /**
     * @var RbacPermission|null RBAC item object.
     */
    private ?RbacPermission $item = null;

    public function __construct(?RbacPermission $item = null)
    {
        if ($item !== null) {
            $this->setItem($item);
        }
    }

    /**
     * Alias for authManager getPermission.
     */
    public static function find(string $name): ?self
    {
        $item = Yii::$app->authManager->getPermission($name);
        return $item ? new self($item) : null;
    }

    /**
     * Return key-value pairs of all permission names.
     */
    public static function getList(): array
    {
        $data = Yii::$app->authManager->getPermissions();
        return ArrayHelper::map($data, 'name', 'name');
    }

    /**
     * Find route wildcard permission (controller/*).
     * Creates if not exists
     *
     * @param string $baseName
     * @param string $descrPrefix
     *
     * @return RbacPermission|null
     * @throws Exception
     */
    public static function getWildcard(string $baseName, string $descrPrefix = 'Access '): ?RbacPermission
    {
        if (!str_contains($baseName, '/')) {
            return null;
        }

        $wildcardName = dirname($baseName) . '/*';

        if (!isset(static::$itemsCache[$wildcardName])) {
            $permission = Yii::$app->authManager->getPermission($wildcardName);
            if (!$permission) {
                $permission = static::create($wildcardName, $descrPrefix . $wildcardName);
            }

            static::$itemsCache[$wildcardName] = $permission;
        }
        return static::$itemsCache[$wildcardName];
    }

    /**
     * Create a new permission.
     * @throws Exception
     * @throws \Exception
     */
    public static function create(string $name, string $description, $rule = null, $parents = []): RbacPermission
    {
        $auth = Yii::$app->authManager;

        // create permission
        $p = $auth->createPermission($name);
        $p->description = $description;
        if ($rule) {
            $p->ruleName = $rule->name;
        }
        $auth->add($p);

        // assign parents
        foreach ($parents as $parent) {
            $auth->addChild($parent, $p);
        }

        return $p;
    }

    /**
     * Build map of parents and childs
     *
     * @param array $names
     *
     * @return array
     */
    public static function getParentChildMap(array $names = []): array
    {
        if (empty($names)) {
            $names = array_keys(Yii::$app->authManager->getPermissions());
        }

        $cacheKey = md5(serialize($names));

        if (!isset(static::$parentsCache[$cacheKey])) {
            $parents = [];
            $childs = [];

            foreach ($names as $parentName) {
                $children = Yii::$app->authManager->getChildren($parentName);
                foreach ($children as $childName => $item) {
                    $childs[$parentName][$childName] = $childName;
                    $parents[$childName][$parentName] = $parentName;
                }
            }

            static::$parentsCache[$cacheKey] = [$parents, $childs];
        }


        return static::$parentsCache[$cacheKey];
    }

    /**
     * Permission direct children permissions.
     */
    public function getChildren(): array
    {
        return Yii::$app->authManager->getChildren($this->item->name);
    }

    /**
     * Get the RBAC item.
     *
     */
    public function getItem(): ?RbacPermission
    {
        return $this->item;
    }

    /**
     * Set the RBAC item.
     */
    public function setItem(?RbacPermission $item): void
    {
        $this->item = $item;
    }

    /**
     * Permission direct parent permissions.
     */
    public function getParents(): array
    {
        $parents = [];
        $permissions = Yii::$app->authManager->getPermissions();
        foreach ($permissions as $perm) {
            if (Yii::$app->authManager->hasChild($perm, $this->item)) {
                $parents[$perm->name] = $perm;
            }
        }

        return $parents;
    }

    /**
     * Permission roles it's assigned to
     *
     * @return \yii\rbac\Role[]
     */
    public function getRoles(): array
    {
        $parents = [];
        $roles = Yii::$app->authManager->getRoles();
        foreach ($roles as $role) {
            $permissions = Yii::$app->authManager->getPermissionsByRole($role->name);
            if (isset($permissions[$this->item->name])) {
                $role->data['_inherit'] = !Yii::$app->authManager->hasChild($role, $this->item);
                $parents[$role->name] = $role;
            }
        }

        return $parents;
    }
}
