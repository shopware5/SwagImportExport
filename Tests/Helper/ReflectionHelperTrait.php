<?php
declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Tests\Helper;

use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;

trait ReflectionHelperTrait
{
    /**
     * @param class-string $className
     */
    public function getReflectionMethod(string $className, string $methodName): ReflectionMethod
    {
        $method = (new ReflectionClass($className))->getMethod($methodName);
        $method->setAccessible(true);

        return $method;
    }

    /**
     * @param class-string $className
     */
    public function getReflectionProperty(string $className, string $methodName): ReflectionProperty
    {
        $property = (new ReflectionClass($className))->getProperty($methodName);
        $property->setAccessible(true);

        return $property;
    }
}
