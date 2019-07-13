<?php

namespace justcoded\yii2\rbac\filters;

use Yii;
use yii\base\Action;
use yii\base\ActionFilter;
use yii\filters\AccessRule;
use yii\web\ErrorAction;
use yii\web\ForbiddenHttpException;

class RouteAccessControl extends ActionFilter
{
	/**
	 * List of action that not need to check access.
	 *
	 * @var array
	 */
	public $allowActions = [];

	/**
	 * Allow route pattern
	 * in debug mode default value is "/^(site|gii|debug)\//i".
	 *
	 * @var string
	 */
	public $allowRegexp = '/^(site)\//i';

	/**
	 * Creates controller/action permission automatically if they are missing in debug mode
	 *
	 * @var bool
	 */
	public $autoCreatePermissions = true;

	/**
	 * RouteAccessControl constructor.
	 *
	 * @param array $config
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
	 */
	public function beforeAction($action)
	{
		// check for error action, otherwise and prevent throwing new exception.
		if (is_a($action, ErrorAction::class)) {
			return true;
		}

		$action_rule = "{$action->controller->uniqueId}/{$action->id}";
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
			return false;
		} else {
			return true;
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

	/**
	 * Auto Create Permissions
	 * in debug mode create permissions automatically and assign them to master.
	 *
	 * @param string $action_rule
	 * @param string $controller_rule
	 */
	protected function autoCreatePermissions($action_rule, $controller_rule)
	{
		if (! YII_DEBUG && $this->autoCreatePermissions) {
			return;
		}
		
		$auth = \Yii::$app->authManager;
		if (! $auth->getPermission($action_rule)) {
			$perm = $auth->createPermission($action_rule);
			$perm->description = 'Route ' . $action_rule;
			$auth->add($perm);
			
			if (! $auth->getPermission($controller_rule)) {
				$perm = $auth->createPermission($controller_rule);
				$perm->description = 'Route ' . $controller_rule;
				$auth->add($perm);
			}
		}
	}
}
