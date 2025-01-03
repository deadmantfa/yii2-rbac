<?php

declare(strict_types=1);

namespace deadmantfa\yii2\rbac\helpers;

use Exception;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RecursiveRegexIterator;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use RegexIterator;
use Yii;
use yii\helpers\Inflector;
use yii\web\Controller;

class ScanHelper
{
    /**
     * Recursively scans a directory to find controller files.
     *
     * @param string $directory
     * @param array $ignorePaths
     * @return array
     */
    public static function scanControllers(string $directory, array $ignorePaths = []): array
    {
        // Set up directory iterators
        $dirIterator = new RecursiveDirectoryIterator($directory);
        $iterator = new RecursiveIteratorIterator($dirIterator);

        // Find all controller files
        $allControllers = new RegexIterator(
            $iterator,
            '/^.+Controller\.php$/',
            RecursiveRegexIterator::GET_MATCH
        );

        $controllerPaths = array_keys(iterator_to_array($allControllers));

        // Filter out ignored paths
        if (!empty($ignorePaths)) {
            $ignorePattern = '(' . implode('|', $ignorePaths) . ')';
            $ignoredControllers = new RegexIterator(
                $allControllers,
                "#^.+$ignorePattern.+Controller\.php$#",
                RecursiveRegexIterator::GET_MATCH
            );

            $ignoredPaths = array_keys(iterator_to_array($ignoredControllers));
            $controllerPaths = array_diff($controllerPaths, $ignoredPaths);
        }

        return $controllerPaths;
    }

    /**
     * Scans controller files to find their public action routes.
     *
     * @param array $controllerFiles
     * @return array
     */
    public static function scanControllerActionIds(array $controllerFiles): array
    {
        $actions = [];

        foreach ($controllerFiles as $filePath) {
            $namespace = self::getNamespaceFromFile($filePath);
            $className = self::getClassNameFromFile($filePath);

            if ($namespace === null || $className === null) {
                continue;
            }

            $fullyQualifiedClassName = "\\$namespace\\$className";

            try {
                $reflection = new ReflectionClass($fullyQualifiedClassName);
            } catch (ReflectionException) {
                Yii::warning("RBAC Scanner: Failed to reflect class $fullyQualifiedClassName.");
                continue;
            }

            if (!$reflection->isSubclassOf(Controller::class)) {
                continue;
            }

            $moduleId = self::getModuleIdFromNamespace($reflection->getNamespaceName());
            $controllerId = Inflector::slug(Inflector::camel2words($className));

            $actions = array_merge($actions, self::getPublicActions($reflection, $moduleId, $controllerId));
        }

        return $actions;
    }

    /**
     * Extracts the namespace from a file's content.
     *
     * @param string $filePath
     * @return string|null
     */
    private static function getNamespaceFromFile(string $filePath): ?string
    {
        $content = file_get_contents($filePath);
        if (preg_match('/namespace\s+([a-z0-9_\\\\]+)/i', $content, $match)) {
            return $match[1];
        }
        return null;
    }

    /**
     * Extracts the class name from a file's content.
     *
     * @param string $filePath
     * @return string|null
     */
    private static function getClassNameFromFile(string $filePath): ?string
    {
        $content = file_get_contents($filePath);
        if (preg_match('/class\s+([a-z0-9_]+)Controller/i', $content, $match)) {
            return $match[1];
        }
        return null;
    }

    /**
     * Extracts the module ID from a namespace.
     *
     * @param string $namespace
     * @return string
     */
    private static function getModuleIdFromNamespace(string $namespace): string
    {
        if (preg_match('/modules\\\\([a-z0-9_-]+)\\\\/i', $namespace, $match)) {
            return Inflector::slug(Inflector::camel2words($match[1])) . '/';
        }
        return '';
    }

    /**
     * Retrieves public action routes from a reflected controller.
     *
     * @param ReflectionClass $reflection
     * @param string $moduleId
     * @param string $controllerId
     * @return array
     */
    private static function getPublicActions(ReflectionClass $reflection, string $moduleId, string $controllerId): array
    {
        $actions = [];
        $methods = $reflection->getMethods(ReflectionMethod::IS_PUBLIC);

        foreach ($methods as $method) {
            $methodName = $method->getName();

            if (preg_match('/^action([A-Z][a-zA-Z0-9]*)$/', $methodName, $match)) {
                $actionId = Inflector::slug(Inflector::camel2words($match[1]));
                $actions[] = $moduleId . $controllerId . '/' . $actionId;
            } elseif ($methodName === 'actions' && $reflection->getName() === $method->class) {
                $actions = array_merge($actions, self::getCustomActions($method, $controllerId, $moduleId));
            }
        }

        return $actions;
    }

    /**
     * Retrieves custom actions from the `actions()` method of a controller.
     *
     * @param ReflectionMethod $method
     * @param string $controllerId
     * @param string $moduleId
     * @return array
     */
    private static function getCustomActions(ReflectionMethod $method, string $controllerId, string $moduleId): array
    {
        $actions = [];
        try {
            $controller = Yii::createObject($method->class, [$controllerId, Yii::$app]);
            $customActions = $controller->actions();

            foreach ($customActions as $actionId => $params) {
                $actions[] = $moduleId . $controllerId . '/' . $actionId;
            }
        } catch (Exception) {
            Yii::warning("RBAC Scanner: Failed to scan custom actions from $method->class::actions().");
        }

        return $actions;
    }
}
