<?php

declare(strict_types=1);

namespace deadmantfa\yii2\rbac\models;


use Exception;
use Yii;
use yii\helpers\ArrayHelper;
use yii\rbac\Role as RbacRole;

class Role extends Item
{
    /**
     * @var RbacRole Rbac item object.
     */
    private RbacRole $item;

    /**
     * Role constructor.
     *
     * @param RbacRole|null $item
     */
    public function __construct(RbacRole $item = null)
    {
        if ($item) {
            $this->setItem($item);
        }
    }

    /**
     * Alias for authManager getPermission
     *
     * @param string $name
     *
     * @return null|Role
     */
    public static function find(string $name): ?Role
    {
        if ($item = Yii::$app->authManager->getRole($name)) {
            return new Role($item);
        }
        return null;
    }

    /**
     * Return key-value pairs of all roles names
     *
     * @return array
     */
    public static function getList(): array
    {
        $data = Yii::$app->authManager->getRoles();

        return ArrayHelper::map($data, 'name', 'name');
    }

    /**
     * Create role inside application authManager
     *
     * @param string $name
     * @param string $descr
     *
     * @return RbacRole
     * @throws Exception
     */
    public static function create(string $name, string $descr): RbacRole
    {
        $auth = Yii::$app->authManager;

        // create permission
        $r = $auth->createRole($name);
        $r->description = $descr;
        $auth->add($r);

        return $r;
    }

    public static function getPermissionsRecursive($roleName): array
    {
        $results = [];

        $children = Yii::$app->authManager->getChildren($roleName);
        foreach ($children as $name => $item) {
            if (Role::TYPE_ROLE == $item->type) {
                continue;
            }

            $results[$name] = $name;
            $results += static::getPermissionsRecursive($name);
        }

        return $results;
    }

    /**
     * @return RbacRole
     */
    public function getItem(): RbacRole
    {
        return $this->item;
    }

    /**
     * @param RbacRole|null $item
     */
    public function setItem(RbacRole $item = null): void
    {
        $this->item = $item;
    }
}