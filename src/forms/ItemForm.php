<?php

declare(strict_types=1);

namespace deadmantfa\yii2\rbac\forms;

use yii\base\Model;

abstract class ItemForm extends Model
{
    const SCENARIO_CREATE = 'create';

    public string $name = ''; // Default value
    public int $type = 0; // Default value
    public ?string $description = null;
    public ?string $ruleName = null;
    public ?string $data = null;
    public ?int $createdAt = null;
    public ?int $updatedAt = null;

    public function rules(): array
    {
        return [
            ['name', 'match', 'pattern' => static::getNamePattern()],
            [['type', 'name'], 'required'],
            ['name', 'uniqueItemName', 'on' => static::SCENARIO_CREATE],
            [['name', 'ruleName'], 'trim'],
            [['name', 'description', 'ruleName', 'data'], 'string'],
            [['type', 'createdAt', 'updatedAt'], 'integer'],
        ];
    }

    /**
     * RBAC Item name validation pattern
     */
    public static function getNamePattern(): string
    {
        return '/^[a-z0-9\s\_\-\/\*]+$/i';
    }

    /**
     * Validate item (permission/role) name to be unique
     *
     */
    abstract public function uniqueItemName(string $attribute, array $params, mixed $validator): bool;

    /**
     * @inheritdoc
     */
    public function attributeLabels(): array
    {
        return [
            'name' => 'Name',
            'description' => 'Description',
            'ruleName' => 'Rule Class',
        ];
    }
}
