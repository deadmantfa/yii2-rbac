<?php

declare(strict_types=1);

namespace deadmantfa\yii2\rbac\commands;

use deadmantfa\yii2\rbac\forms\ScanForm;
use deadmantfa\yii2\rbac\models\Item;
use Yii;
use yii\base\Exception;
use yii\console\Controller;
use yii\console\ExitCode;
use yii\helpers\Console;
use yii\rbac\Permission;
use yii\rbac\Role;
use yii\rbac\Rule;

class RbacController extends Controller
{
    private const PROMPT_CONFIRMATION = 'Are you sure you want to continue? (yes/no)';

    /**
     * @var string Path to scan.
     */
    public string $path = '@app';

    /**
     * @var array Paths to ignore.
     */
    public array $ignorePath = [];

    /**
     * @inheritdoc
     */
    public function options($actionID): array
    {
        return $actionID === 'scan' ? ['path', 'ignorePath'] : parent::options($actionID);
    }

    /**
     * @inheritdoc
     */
    public function optionAliases(): array
    {
        return [
            'p' => 'path',
            'i' => 'ignorePath',
        ];
    }

    /**
     * Initializes default roles, master, and administer permissions.
     *
     * @throws Exception
     */
    public function actionInit(): int
    {
        $this->log('Init command will clean all your current roles, permissions, and assignments.', Console::FG_YELLOW);
        $confirm = $this->prompt(self::PROMPT_CONFIRMATION, ['required' => true]);
        if (strtolower($confirm)[0] !== 'y') {
            $this->log("\nTerminating.", Console::FG_RED);
            return ExitCode::DATAERR;
        }

        $am = Yii::$app->authManager;
        $am->removeAll();

        $everything = $this->addPermission(Item::PERMISSION_MASTER, 'Allow Everything');
        $administer = $this->addPermission(Item::PERMISSION_ADMINISTER, 'Access administration panel.');

        $this->addRole(Item::ROLE_GUEST, 'Usual site visitor.');
        $this->addRole(Item::ROLE_AUTHENTICATED, 'Authenticated user.');
        $this->addRole(Item::ROLE_ADMIN, 'Administrator.', [$administer]);
        $this->addRole(Item::ROLE_MASTER, 'Has full system access.', [$everything]);

        $this->log("Finished.", Console::FG_GREEN);
        return ExitCode::OK;
    }

    /**
     * Unified logging method with ANSI color support.
     */
    protected function log(string $message, int $color = Console::FG_GREY): void
    {
        $this->stdout($message . "\n", $color);
    }

    /**
     * Adds a permission to the RBAC system.
     *
     * @param Rule|null $rule
     * @throws Exception
     * @throws \Exception
     */
    protected function addPermission(string $name, string $descr, Rule $rule = null, array $parents = []): Permission
    {
        $auth = Yii::$app->authManager;

        $permission = $auth->createPermission($name);
        $permission->description = $descr;
        if ($rule !== null) {
            $permission->ruleName = $rule->name;
        }
        $auth->add($permission);
        $this->log("Added permission: $permission->name", Console::FG_GREEN);

        foreach ($parents as $parent) {
            $auth->addChild($parent, $permission);
            $this->log("Added as child of: $parent->name", Console::FG_GREEN);
        }

        return $permission;
    }

    /**
     * Adds a role to the RBAC system.
     *
     * @throws Exception
     * @throws \Exception
     */
    protected function addRole(string $name, string $descr, array $childs = []): Role
    {
        $auth = Yii::$app->authManager;

        $role = $auth->createRole($name);
        $role->description = $descr;
        $auth->add($role);
        $this->log("Added role: $role->name", Console::FG_GREEN);

        foreach ($childs as $child) {
            $auth->addChild($role, $child);
            $this->log("Added '$child->name' to $role->name", Console::FG_GREEN);
        }

        return $role;
    }

    /**
     * Assigns the master role to a user.
     *
     * @throws \Exception
     */
    public function actionAssignMaster(int $userId): int
    {
        if ($userId <= 0) {
            $this->log("User ID should be a positive integer.", Console::FG_RED);
            return ExitCode::DATAERR;
        }

        $am = Yii::$app->authManager;
        $master = $am->getRole(Item::ROLE_MASTER);
        if (!$master) {
            $this->log("Master role not found. Please run 'init' command first.", Console::FG_RED);
            return ExitCode::UNAVAILABLE;
        }

        $am->assign($master, $userId);
        $this->log("Role " . Item::ROLE_MASTER . " added to user $userId.", Console::FG_GREEN);

        return ExitCode::OK;
    }

    /**
     * Assigns permissions or roles to users.
     *
     * @throws \Exception
     */
    public function actionAssign(array $items, array $userIds): int
    {
        if (empty($items) || empty($userIds)) {
            $this->log("Both items and user IDs are required.", Console::FG_RED);
            return ExitCode::DATAERR;
        }

        $am = Yii::$app->authManager;

        foreach ($items as $item) {
            $authItem = $am->getRole($item) ?? $am->getPermission($item);
            if (!$authItem) {
                $this->log("Role or permission '$item' not found.", Console::FG_RED);
                continue;
            }

            foreach ($userIds as $userId) {
                Yii::$app->authManager->assign($authItem, $userId);
                $this->log("Assigned '$authItem->name' to user $userId.", Console::FG_GREEN);
            }
        }

        $this->log("Finished assigning items.", Console::FG_GREEN);
        return ExitCode::OK;
    }

    /**
     * Scans controller/module directories to get route permissions info.
     *
     * @throws Exception
     */
    public function actionScan(): int
    {
        $scanner = new ScanForm();
        $scanner->path = $this->path;
        $scanner->ignorePath = $this->ignorePath;

        $this->log('Scanning directory for controllers and routes...', Console::FG_YELLOW);

        $actionRoutes = $scanner->scan();
        if ($actionRoutes) {
            $this->log("Found " . count($actionRoutes) . ' routes.', Console::FG_GREEN);
            foreach ($scanner->importPermissions($actionRoutes) as $route => $status) {
                $this->log("Added route: $route", Console::FG_GREEN);
            }
        } else {
            $this->log("No routes found during the scan.", Console::FG_YELLOW);
        }

        return ExitCode::OK;
    }
}
