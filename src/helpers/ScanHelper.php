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
                RecursiveRegexIterator::GET_MATCH,
                RecursiveRegexIterator::USE_KEY
            );

            $ignoredPaths = array_keys(iterator_to_array($ignoredControllers));
            $controllerPaths = array_diff($controllerPaths, $ignoredPaths);
        }

        return $controllerPaths;
    }

    /**
     * Scans controller files to find their public action routes.
     *
     * @throws ReflectionException
     */
    public static function scanControllerActionIds(array $controllerFiles): array
    {
        $actions = [];
        foreach ($controllerFiles as $filename) {
            $content = file_get_contents($filename);
            $content = preg_replace(
                "/(\/\*([^*]|[\r\n]|(\*+([^*\/]|[\r\n])))*\*\/)|(\/\/.*)/i",
                '',
                $content
            );
            // ignore abstract classes
            if (str_contains($content, 'abstract class')) {
                continue;
            }

            if (!preg_match('/namespace\s+([a-z0-9_\\\\]+)/i', $content, $namespaceMatch)) {
                continue;
            }

            if (!preg_match('/class\s+(([a-z0-9_]+)Controller)[^{]+{/i', $content, $classMatch)) {
                continue;
            }

            $className = '\\' . $namespaceMatch[1] . '\\' . $classMatch[1];
            $reflection = new ReflectionClass($className);

            // ignore console commands
            if (!$reflection->isSubclassOf(Controller::class)) {
                continue;
            }

            // find public methods
            $methods = $reflection->getMethods(ReflectionMethod::IS_PUBLIC);

            $moduleId = '';
            if (preg_match('/modules\\\\([a-z0-9_-]+)\\\\/i', $reflection->getNamespaceName(), $moduleMatch)) {
                $moduleId = Inflector::slug(Inflector::camel2words($moduleMatch[1])) . '/';
            }

            foreach ($methods as $method) {
                if (!preg_match('/^action([A-Z]([a-zA-Z0-9]+))$/', $method->getName(), $actionMatch)
                    && !('actions' === $method->getName() && $reflection->getName() === $method->class)
                ) {
                    continue;
                }
                $controllerId = Inflector::slug(Inflector::camel2words($classMatch[2]));

                if ('actions' === $method->getName()) {
                    try {
                        $controllerObj = Yii::createObject($method->class, [$controllerId, Yii::$app]);
                        $customActions = $controllerObj->actions();
                        foreach ($customActions as $actionId => $params) {
                            $actions[] = $moduleId . $controllerId . '/' . $actionId;
                        }
                    } catch (Exception $e) {
                        Yii::warning("RBAC Scanner: can't scan custom actions from {$method->class}::actions(). You will need to add them manually.");
                    }

                } else {
                    $actionId = Inflector::slug(Inflector::camel2words($actionMatch[1]));
                    $actions[] = $moduleId . $controllerId . '/' . $actionId;
                }
            }
        }

        return $actions;
    }
}
