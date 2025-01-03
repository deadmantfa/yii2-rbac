<?php

declare(strict_types=1);

namespace deadmantfa\yii2\rbac\forms;

use deadmantfa\yii2\rbac\models\Permission;
use Exception;
use ReflectionClass;
use Yii;
use yii\base\InvalidConfigException;
use yii\helpers\ArrayHelper;
use yii\rbac\Item;
use yii\rbac\Rule;


class PermissionForm extends ItemForm
{
    /**
     * @var string
     */
    public string $ruleClass;

    /**
     * @var Permission
     */
    protected Permission $permission;

    /**
     * @inheritdoc
     */

    public function init(): void
    {
        $this->type = Item::TYPE_PERMISSION;
        $this->permission = new Permission();
    }

    /**
     * @inheritdoc
     * @return array
     */
    public function rules(): array
    {
        return ArrayHelper::merge(parent::rules(), [
            ['ruleClass', 'match', 'pattern' => '/^[a-z][\w\d\_\\\]*$/i'],
            ['ruleClass', 'validRuleClass', 'skipOnEmpty' => true],
        ]);
    }

    /**
     * @inheritdoc
     */
    public function attributeHints(): array
    {
        return [
            'ruleClass' => 'This is the name of RBAC Rule class to be generated. 
					It should be a fully qualified namespaced class name, 
					e.g., <code>app\rbac\MyRule</code>',
        ];
    }

    /**
     * @inheritdoc
     */
    public function uniqueItemName(string $attribute, array $params, mixed $validator): bool
    {
        unset($params, $validator); // Suppress unused warnings
        $name = $this->$attribute;
        if (Permission::find($name)) {
            $this->addError($attribute, 'Permission with the same name already exists.');
            return false;
        }
        return true;
    }

    /**
     * Validate Rule Class to be namespaced class name and instance of yii\rbac\Rule
     *
     * @param string $attribute
     * @param array $params
     * @param mixed $validator
     *
     * @return bool
     */
    public function validRuleClass(string $attribute, array $params, mixed $validator): bool
    {
        unset($params, $validator); // Suppress unused warnings
        $class = $this->$attribute;
        if (!class_exists($class)) {
            $this->addError($attribute, 'Not valid class name.');
            return false;
        } else {
            $reflect = new ReflectionClass($class);
            if (!$reflect->isSubclassOf(Rule::class)) {
                $this->addError($attribute, 'Class have to be extended of \\yii\\rbac\\Rule class');
                return false;
            }
        }

        return true;
    }

    /**
     * Load permission data to properties and set correct ruleClass
     *
     * @param Permission $permission
     */
    public function setPermission(Permission $permission): void
    {
        $this->permission = $permission;
        $this->load((array)$permission->getItem(), '');
        $this->ruleClass = $this->getRuleClassName();
    }

    /**
     * Find rule namespaced class name by current ruleName
     *
     * @return null|string
     */
    public function getRuleClassName(): ?string
    {
        if ($this->ruleName) {
            $rule = Yii::$app->authManager->getRule($this->ruleName);
            return get_class($rule);
        }
        return null;
    }

    /**
     * Get RBAC Rule object
     * create/register in case rule doesn't exists
     *
     * @param string $className
     *
     * @return Rule
     * @throws InvalidConfigException
     * @throws Exception
     */
    public function getRule(string $className): Rule
    {
        $rules = Yii::$app->authManager->getRules();
        foreach ($rules as $rule) {
            if (get_class($rule) === $className) {
                return $rule;
            }
        }

        // Create and register the rule if it doesn't exist
        $rule = Yii::createObject("\\" . $className);
        if (!$rule instanceof Rule) {
            throw new InvalidConfigException("The class $className must extend \\yii\\rbac\\Rule.");
        }

        Yii::$app->authManager->add($rule);
        return $rule;
    }

    /**
     * Create single permission with rule name
     *
     * @return bool
     * @throws InvalidConfigException
     * @throws Exception
     */
    public function save(): bool
    {
        if (!$this->validate()) {
            return false;
        }

        if (!$item = $this->permission->getItem()) {
            $item = Permission::create($this->name, $this->description);
        }

        $item->description = $this->description;
        if ($this->ruleClass) {
            $rule = $this->getRule($this->ruleClass);
            $item->ruleName = $rule->name;
        }


        return Yii::$app->authManager->update($item->name, $item);
    }

}