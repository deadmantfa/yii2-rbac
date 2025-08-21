<?php

declare(strict_types=1);

namespace deadmantfa\yii2\rbac\models;

use Yii;
use yii\base\Model;
use yii\data\ArrayDataProvider;
use yii\rbac\Permission as RbacPermission;
use yii\rbac\Role as RbacRole;


class ItemSearch extends Model
{
    /**
     * @var string|null Role name for search.
     */
    public ?string $roleName = null;

    /**
     * @var string|null Permission name for search.
     */
    public ?string $permName = null;

    /**
     * @var string|null Selected role name for filtering permissions.
     */
    public ?string $permRole = null;


    /**
     * @param $permissionName
     */
    public static function getRoleByPermission($permissionName): array
    {
        $roles = Yii::$app->authManager->getRoles();

        $array = [];
        foreach ($roles as $role) {
            $permissions = Yii::$app->authManager->getPermissionsByRole($role->name);
            foreach ($permissions as $permission) {
                if ($permissionName == $permission->name) {
                    $array[] = $role->name;
                }
            }

        }

        return $array;
    }

    /**
     * @param $parent
     */
    public static function getInherit($parent): array
    {
        $array = [];
        if ($children = Yii::$app->authManager->getChildren($parent)) {
            foreach ($children as $child) {
                if ($child->type == Item::TYPE_ROLE) {
                    $array[] = $child->name;
                }
            }
        }
        return $array;
    }

    /**
     * @inheritdoc
     */
    public function rules(): array
    {
        $roles = array_keys(Yii::$app->authManager->getRoles());
        return [
            [['roleName', 'permName'], 'string'],
            [['permRole'], 'in', 'range' => $roles],
        ];
    }

    /**
     * @param $params
     */
    public function searchRoles($params): ArrayDataProvider
    {
        $this->load($params);

        $roles = Yii::$app->authManager->getRoles();
        if ($this->roleName) {
            $roles = array_filter($roles, fn(RbacRole $role): bool => str_contains(strtolower($role->name), $this->roleName));
        }

        return new ArrayDataProvider([
            'allModels' => $roles,
            'pagination' => [
                'pageSize' => 20,
            ],
        ]);
    }

    /**
     * @param $params
     */
    public function searchPermissions($params): ArrayDataProvider
    {
        $this->load($params);

        $permissions = $this->permRole ? Yii::$app->authManager->getPermissionsByRole($this->permRole) :
            Yii::$app->authManager->getPermissions();

        if ($this->permName) {
            $permissions = array_filter($permissions, fn(RbacPermission $permission): bool => str_contains(strtolower($permission->name), $this->permName));
        }

        return new ArrayDataProvider([
            'allModels' => $permissions,
            'pagination' => [
                'pageSize' => 20,
            ],
        ]);
    }
}
