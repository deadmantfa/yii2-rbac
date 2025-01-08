<?php

declare(strict_types=1);

namespace deadmantfa\yii2\rbac\forms;

use deadmantfa\yii2\rbac\helpers\ScanHelper;
use deadmantfa\yii2\rbac\models\Permission;
use ReflectionException;
use Yii;
use yii\base\Exception;
use yii\base\Model;

class ScanForm extends Model
{
    const SCENARIO_WEB = 'web';

    /**
     * Path to scan.
     */
    public $path;

    /**
     * Paths to ignore.
     * Use comma to specify several paths.
     */
    public $ignorePath;

    /**
     * Routes base prefix to be added to all found routes.
     */
    public $routesBase;

    /**
     * Internal items cache array to speed up some operations.
     */
    protected $itemsCache;

    /**
     * @inheritdoc
     */
    public function rules(): array
    {
        return [
            [['path'], 'required'],
            [['path', 'routesBase'], 'string'],

            [['path'], 'default', 'value' => '@app', 'on' => static::SCENARIO_WEB],
            [['routesBase'], 'default', 'value' => ''],

            [['path'], 'filter', 'filter' => function ($value) {
                return Yii::getAlias($value);
            }],
            [['ignorePath'], 'default', 'value' => []],
            [['ignorePath'], 'filter', 'filter' => function ($value): array {
                if (!is_array($value)) {
                    $value = explode(',', trim($value));
                }
                return $value;
            }],

            [['path'], 'validDir'],
        ];
    }

    /**
     * Validate that passed value is a real directory path.
     */
    public function validDir(string $attribute): bool
    {
        if (!is_dir($this->$attribute)) {
            $this->addError(
                $attribute,
                Yii::t('app', '{attr} must be a directory.', [
                    'attr' => $this->getAttributeLabel($attribute)
                ])
            );
            return false;
        }
        return true;
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels(): array
    {
        return [
            'path' => 'Path to Scan',
            'ignorePath' => 'Paths to Ignore',
            'routesBase' => 'Base path for found routes',
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeHints(): array
    {
        return [
            'path' => 'Server path or Yii alias for directory to scan.<br> 
				Examples: <code>@app</code>, <code>@vendor/deadmantfa/yii2-rbac</code>',
            'ignorePath' => 'Comma separate list of directories to ignore, RegExp syntax is allowed.',
            'routesBase' => 'Useful for 3rd party modules, which added inside your application. <br>
				For example, for "rbac" module added to "admin" module, 
				you can specify <code>admin/rbac/</code> base path',
        ];
    }

    /**
     * Run routes scan.
     * @throws ReflectionException
     */
    public function scan(): bool|array
    {
        if (!$this->validate()) {
            return false;
        }

        $controllers = ScanHelper::scanControllers($this->path, $this->ignorePath);
        $actionRoutes = ScanHelper::scanControllerActionIds($controllers);

        if ($actionRoutes === []) {
            $this->addError('path', 'Unable to find controllers/actions.');
            return false;
        }

        return $actionRoutes;
    }

    /**
     * Import permissions with wildcards, if they have / inside.
     * @throws Exception
     */
    public function importPermissions(array $permissions): array
    {
        $auth = Yii::$app->authManager;

        $inserted = [];
        foreach ($permissions as $route) {
            $route = $this->routesBase . $route;

            if (!$auth->getPermission($route)) {
                $wildcard = Permission::getWildcard($route, 'Route ');
                $permission = Permission::create($route, 'Route ' . $route, null, [$wildcard]);

                $inserted[$wildcard->name] = 1;
                $inserted[$permission->name] = 1;
            }
        }

        return $inserted;
    }
}
