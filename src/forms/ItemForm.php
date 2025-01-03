<?php

declare(strict_types=1);

namespace deadmantfa\yii2\rbac\forms;

use yii\base\Model;

abstract class ItemForm extends Model
{
    const SCENARIO_CREATE = 'create';

    public string $name;
    public int $type;
    public ?string $description;
    public ?string $ruleName;
    public ?string $data;
    public ?int $createdAt;
    public ?int $updatedAt;

    /**
     * @return array
     */
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
     *
     * @return string
     */
    public static function getNamePattern(): string
    {
        return '/^[a-z0-9\s\_\-\/\*]+$/i';
    }

    /**
     * Validate item (permission/role) name to be unique
     *
     * @param string $attribute
     * @param array $params
     * @param mixed $validator
     *
     * @return bool
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