<?php
/**
 * Created for DiBify.
 * Datetime: 15.02.2018 11:25
 * @author Timur Kasumov aka XAKEPEHOK
 */

namespace DiBify\DiBify\Mappers;


use DiBify\DiBify\Exceptions\SerializerException;
use DiBify\DiBify\Helpers\ReflectionHelper;
use ReflectionException;

class ObjectMapper implements MapperInterface
{

    /** @var string */
    protected $classname;

    /** @var MapperInterface[] */
    protected $mappers;

    /**
     * ObjectMapper constructor.
     * @param string $classname
     * @param MapperInterface[] $mappers
     */
    public function __construct(string $classname, array $mappers)
    {
        $this->classname = $classname;
        $this->mappers = $mappers;
    }

    /**
     * Convert complex data (like object) to simpe data (scalar, null, array)
     * @param $complex
     * @return array
     * @throws SerializerException
     * @throws ReflectionException
     */
    public function serialize($complex)
    {
        if (!is_object($complex) || !is_a($complex, $this->classname)) {
            $type = is_object($complex) ? get_class($complex) : gettype($complex);
            throw new SerializerException("Mapper can serialize only '{$this->classname}', but '{$type}' passed");
        }

        $data = [];

        foreach ($this->mappers as $property => $mapper) {
            try {
                $data[$property] = $mapper->serialize(
                    ReflectionHelper::getProperty(
                        $complex,
                        $property
                    )
                );
            } catch (SerializerException $exception) {
                throw new SerializerException(
                    "Object serialization of '{$property}': {$exception->getMessage()}",
                    $exception->getCode(),
                    $exception
                );
            }
        }

        return $data;
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
        if (!is_array($data)) {
            $type = gettype($data);
            throw new SerializerException("Serialized data of {$this->classname} should be array, but '{$type}' type passed");
        }

        $object = ReflectionHelper::newWithoutConstructor($this->classname);
        foreach ($this->mappers as $property => $mapper) {
            $value = $data[$property] ?? null;
            try {
                ReflectionHelper::setProperty(
                    $object,
                    $property,
                    $mapper->deserialize($value)
                );
            } catch (SerializerException $exception) {
                throw new SerializerException(
                    "Object deserialization of '{$property}': {$exception->getMessage()}",
                    $exception->getCode(),
                    $exception
                );
            }
        }

        return $object;
    }

}