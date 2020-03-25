<?php
/**
 * @author Timur Kasumov (aka XAKEPEHOK)
 * Datetime: 26.06.2017 1:30
 */

namespace DiBify\DiBify\Helpers;


use ArrayAccess;
use ReflectionClass;
use ReflectionException;
use ReflectionProperty;

class ReflectionHelper
{

    /** @var ReflectionClass[] */
    private static $reflectionClasses = [];

    /** @var ReflectionProperty[] */
    private static $reflectionProperties = [];

    /**
     * @param string $class
     * @return object
     * @throws ReflectionException
     */
    public static function newWithoutConstructor(string $class): object
    {
        if (!isset(self::$reflectionClasses[$class])) {
            self::$reflectionClasses[$class] = new ReflectionClass($class);
        }
        return self::$reflectionClasses[$class]->newInstanceWithoutConstructor();
    }

    /**
     * @param $object
     * @param string $property
     * @return mixed
     * @throws ReflectionException
     */
    public static function getProperty(object $object, string $property)
    {
        $className = get_class($object);
        try {
            $refProperty = self::getReflectionProperty($className, $property);
            return $refProperty->getValue($object);
        } catch (ReflectionException $reflectionException) {

            if ($object instanceof ArrayAccess) {
                return $object[$property];
            } else {
                throw $reflectionException;
            }
        }
    }

    /**
     * @param $object
     * @param string $property
     * @param $value
     * @throws ReflectionException
     */
    public static function setProperty(object $object, string $property, $value): void
    {
        $className = get_class($object);
        try {
            $refProperty = self::getReflectionProperty($className, $property);
            $refProperty->setValue($object, $value);
        } catch (ReflectionException $reflectionException) {
            if ($object instanceof ArrayAccess) {
                $object[$property] = $value;
            } else {
                throw $reflectionException;
            }
        }
    }

      /**
     * @param string $className
     * @param string $property
     * @return ReflectionProperty
     * @throws ReflectionException
     */
    private static function getReflectionProperty(string $className, string $property): ReflectionProperty
    {
        $key = $className . '::' . $property;
        if (!isset(self::$reflectionProperties[$key])) {
            self::$reflectionProperties[$key] = new ReflectionProperty($className, $property);
        }
        self::$reflectionProperties[$key]->setAccessible(true);
        return self::$reflectionProperties[$key];
    }

}