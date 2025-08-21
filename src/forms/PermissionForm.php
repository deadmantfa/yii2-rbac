<?php

declare(strict_types=1);

namespace deadmantfa\yii2\rbac\forms;

use deadmantfa\yii2\rbac\models\Permission;
use ReflectionClass;
use Yii;
use yii\base\Exception;
use yii\base\InvalidConfigException;
use yii\helpers\ArrayHelper;
use yii\rbac\Rule;

class PermissionForm extends ItemForm
{
    public ?string $ruleClass = null;

    protected Permission $permission;

    public function init(): void
    {
        parent::init();
        $this->type = Permission::TYPE_PERMISSION;
        $this->permission = new Permission();
    }

    /**
     * @inheritdoc
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
     * Load permission data and set the rule class.
     */
    public function setPermission(Permission $permission): void
    {
        $this->permission = $permission;
        $this->load((array)$permission->getItem(), '');
        $this->ruleClass = $this->getRuleClassName();
    }

    /**
     * Get the rule class name from ruleName.
     */
    private function getRuleClassName(): ?string
    {
        if ($this->ruleName) {
            $rule = Yii::$app->authManager->getRule($this->ruleName);
            return $rule::class;
        }
        return null;
    }

    /**
     * Get the RBAC rule object.
     * @throws InvalidConfigException
     * @throws \Exception
     */
    private function getRule(string $className): Rule
    {
        $rules = Yii::$app->authManager->getRules();
        foreach ($rules as $rule) {
            if ($rule::class === $className) {
                return $rule;
            }
        }

        // no rule found - creating rule
        $rule = Yii::createObject("\\" . $className);
        Yii::$app->authManager->add($rule);
        return $rule;
    }

    /**
     * Create or update a permission.
     *
     * @throws Exception
     * @throws \Exception
     */
    public function save(): bool
    {
        if (!$this->validate()) {
            return false;
        }

        $item = $this->permission->getItem();
        if (!$item instanceof \yii\rbac\Permission) {
            $item = Permission::create($this->name, $this->description);
            // Then store it back into $this->permission
            $this->permission->setItem($item);
        }

        $item->description = $this->description;
        if ($this->ruleClass) {
            $rule = $this->getRule($this->ruleClass);
            $item->ruleName = $rule->name;
        }


        return Yii::$app->authManager->update($item->name, $item);
    }

    /**
     * Validate that the item name is unique.
     */
    public function uniqueItemName(string $attribute, ?array $params, mixed $validator): bool
    {
        unset($params, $validator);
        $name = $this->$attribute;
        if ($item = Permission::find($name) instanceof \deadmantfa\yii2\rbac\models\Permission) {
            $this->addError($attribute, 'Permission with the same name is already exists.');
            return false;
        }
        return true;
    }

    /**
     * Validate Rule Class to be namespaced class name and instance of yii\rbac\Rule
     *
     * @param array $params
     */
    public function validRuleClass(string $attribute, ?array $params, mixed $validator): bool
    {
        unset($params, $validator);
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
}
