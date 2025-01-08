<?php

declare(strict_types=1);

namespace deadmantfa\yii2\rbac\filters;

use Exception;
use Yii;
use yii\base\Action;
use yii\base\ActionFilter;
use yii\web\ErrorAction;
use yii\web\ForbiddenHttpException;

class RouteAccessControl extends ActionFilter
{
    /**
     * List of action that not need to check access.
     */
    public array $allowActions = [];

    /**
     * Allow route pattern
     * in debug mode default value is "/^(site|gii|debug)\//i".
     */
    public string $allowRegexp = '/^(site)\//i';

    /**
     * Creates controller/action permission automatically if they are missing in debug mode
     */
    public bool $autoCreatePermissions = true;

    /**
     * RouteAccessControl constructor.
     */
    public function __construct(array $config = [])
    {
        if (defined('YII_DEBUG') && YII_DEBUG) {
            $this->allowRegexp = '/^(site|gii|debug)\//i';
        }
        parent::__construct($config);
    }

    /**
     * This method is invoked right before an action is to be executed (after all possible filters.)
     * Check $allowActions or RBAC permission for such entries:
     *      {controller}/{action}   (or  {module}/{controller}/{action})
     *      {controller}/*          (or  {module}/{controller}/*)
     *
     * @param Action $action the action to be executed.
     * @return bool whether the action should continue to be executed.
     * @throws ForbiddenHttpException
     * @throws Exception
     */
    public function beforeAction($action): bool
    {
        // check for error action, otherwise and prevent throwing new exception.
        if (is_a($action, ErrorAction::class)) {
            return true;
        }

        $action_rule = "{$action->controller->uniqueId}/$action->id";
        $controller_rule = "{$action->controller->uniqueId}/*";

        if (preg_match($this->allowRegexp, $action_rule)) {
            return true;
        }

        if (in_array($action_rule, $this->allowActions)
            || in_array($controller_rule, $this->allowActions)
        ) {
            $allow = true;
        } else {
            $this->autoCreatePermissions($action_rule, $controller_rule);
            $allow = Yii::$app->user->can($action_rule);
        }

        if (!$allow) {
            $this->denyAccess();
        } else {
            return true;
        }
    }

    /**
     * Auto Create Permissions
     * in debug mode create permissions automatically and assign them to master.
     *
     * @throws Exception
     */
    protected function autoCreatePermissions(string $action_rule, string $controller_rule): void
    {
        if (!YII_DEBUG && $this->autoCreatePermissions) {
            return;
        }

        $auth = Yii::$app->authManager;
        if (!$auth->getPermission($action_rule)) {
            $perm = $auth->createPermission($action_rule);
            $perm->description = 'Route ' . $action_rule;
            $auth->add($perm);

            if (!$auth->getPermission($controller_rule)) {
                $perm = $auth->createPermission($controller_rule);
                $perm->description = 'Route ' . $controller_rule;
                $auth->add($perm);
            }
        }
    }

    /**
     * Deny access method
     *
     * @throws ForbiddenHttpException Deny exception.
     */
    public function denyAccess()
    {
        throw new ForbiddenHttpException('You are not allowed to perform this action.');
    }
}

