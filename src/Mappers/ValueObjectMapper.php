<?php
/**
 * Created for dibify
 * Date: 22.04.2020
 * @author Timur Kasumov (XAKEPEHOK)
 */

namespace DiBify\DiBify\Mappers;


use DiBify\DiBify\Exceptions\SerializerException;
use DiBify\DiBify\Helpers\ReflectionHelper;
use ReflectionException;

class ValueObjectMapper implements MapperInterface
{

    protected string $classname;

    protected string $property;

    protected MapperInterface $mapper;

    public function __construct(string $classname, string $property, MapperInterface $mapper)
    {
        $this->classname = $classname;
        $this->property = $property;
        $this->mapper = $mapper;
    }

    /**
     * Convert complex data (like object) to simple data (scalar, null, array)
     * @param $complex
     * @return array
     * @throws SerializerException
     * @throws ReflectionException
     */
    public function serialize($complex)
    {
        if (!is_object($complex) || !is_a($complex, $this->classname)) {
            throw new SerializerException("Mapper can serialize only {$this->classname}");
        }

        try {
            return $this->mapper->serialize(
                ReflectionHelper::getProperty(
                    $complex,
                    $this->property
                )
            );
        } catch (SerializerException $exception) {
            throw new SerializerException(
                "ValueObject serialization of '{$this->classname}': {$exception->getMessage()}",
                $exception->getCode(),
                $exception
            );
        }
    }

    /**
     * Convert simple data (scalar, null, array) into complex data (like object)
     * @param mixed $data
     * @return object
     * @throws SerializerException
     * @throws ReflectionException
     */
    public function deserialize($data)
    {
        $object = ReflectionHelper::newWithoutConstructor($this->classname);

        try {
            ReflectionHelper::setProperty(
                $object,
                $this->property,
                $this->mapper->deserialize($data)
            );
        } catch (SerializerException $exception) {
            throw new SerializerException(
                "ValueObject deserialization of '{$this->classname}': {$exception->getMessage()}",
                $exception->getCode(),
                $exception
            );
        }

        return $object;
    }
}