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

    /**
     * @var string
     */
    private $classname;
    /**
     * @var MapperInterface[]
     */
    private $mappers;

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
     * Превращает сложный объект в простой тип (scalar, null, array)
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

        $data = [];
        foreach ($this->mappers as $property => $mapper) {
            $data[$property] = $mapper->serialize(
                ReflectionHelper::getProperty(
                    $complex,
                    $property
                )
            );
        }

        return $data;
    }

    /**
     * Превращает простой тип (scalar, null, array) в сложный (object)
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
            ReflectionHelper::setProperty(
                $object,
                $property,
                $mapper->deserialize($value)
            );
        }

        return $object;
    }

}