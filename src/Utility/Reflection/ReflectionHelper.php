<?php

/*
 * This file is part of the `src-run/interface-query-console-app` project.
 *
 * (c) Rob Frawley 2nd <rmf@src.run>
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace App\Utility\Reflection;

final class ReflectionHelper
{
    /**
     * @param string|object      $object
     * @param string             $name
     * @param mixed[]            $arguments
     * @param string|object|null $binding
     *
     * @return mixed
     */
    public static function methodInvoke($object, string $name, array $arguments = [], $binding = null)
    {
        return ($method = self::reflectMethod($object, $name, true))->invokeArgs(
            $binding ?: ($method->isStatic() || !is_object($object) ? null : $object), $arguments
        );
    }

    /**
     * @param string|object      $object
     * @param string             $name
     * @param string|object|null $binding
     *
     * @return mixed
     */
    public static function propertyValue($object, string $name, $binding = null)
    {
        return ($property = self::reflectProperty($object, $name, true))->getValue(
            $binding ?: ($property->isStatic() || !is_object($object) ? null : $object)
        );
    }

    /**
     * @param string|object $object
     * @param string        $name
     * @param bool          $accessible
     *
     * @return \ReflectionMethod
     */
    public static function reflectMethod($object, string $name, bool $accessible = false): \ReflectionMethod
    {
        try {
            return self::assignAccessibility(
                self::reflectObject($object)->getMethod($name), $accessible
            );
        } catch (\ReflectionException $e) {
            throw new \RuntimeException(
                'Failed to create \ReflectionMethod for "%s::%s()".', self::resolveObjectName($object), $name
            );
        }
    }

    /**
     * @param string|object $object
     * @param string        $name
     * @param bool          $accessible
     *
     * @return \ReflectionProperty
     */
    public static function reflectProperty($object, string $name, bool $accessible = false): \ReflectionProperty
    {
        try {
            return self::assignAccessibility(
                self::reflectObject($object)->getProperty($name), $accessible
            );
        } catch (\ReflectionException $e) {
            throw new \RuntimeException(
                'Failed to create \ReflectionProperty for "%s::$%s".', self::resolveObjectName($object), $name
            );
        }
    }

    /**
     * @param \Reflector|\ReflectionProperty|\ReflectionMethod $reflector
     * @param bool                                             $accessible
     *
     * @return \Reflector|\ReflectionProperty|\ReflectionMethod
     */
    public static function assignAccessibility(\Reflector $reflector, bool $accessible = true): \Reflector
    {
        if ($reflector instanceof \ReflectionMethod || $reflector instanceof \ReflectionProperty) {
            if ($accessible && ($reflector->isProtected() || $reflector->isPrivate())) {
                $reflector->setAccessible(true);
            }
        }

        return $reflector;
    }

    /**
     * @param string|object $object
     *
     * @return \ReflectionClass|\ReflectionObject
     */
    public static function reflectObject($object): \ReflectionClass
    {
        try {
            return is_object($object)
                ? new \ReflectionObject($object)
                : new \ReflectionClass($object);
        } catch (\ReflectionException $e) {
            throw new \RuntimeException(
                'Failed to create \ReflectionClass or \ReflectionObject for "%s".', self::resolveObjectName($object)
            );
        }
    }

    /**
     * @param string|object $object
     *
     * @return string
     */
    private static function resolveObjectName($object): string
    {
        return is_object($object) ? get_class($object) : $object;
    }
}
