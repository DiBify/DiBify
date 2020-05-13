<?php
/**
 * Created for dibify
 * Date: 12.04.2020
 * @author Timur Kasumov (XAKEPEHOK)
 */

namespace DiBify\DiBify\Mappers;


use DiBify\DiBify\Exceptions\SerializerException;
use DiBify\DiBify\Model\ModelInterface;
use DiBify\DiBify\Repository\Storage\StorageData;
use ReflectionException;

class ModelMapper extends ObjectMapper
{

    /** @var string */
    protected $idProperty;

    /**
     * ObjectMapper constructor.
     * @param string $classname
     * @param MapperInterface[] $mappers
     * @param string $idProperty
     * @throws SerializerException
     */
    public function __construct(string $classname, array $mappers, string $idProperty = 'id')
    {
        if (!in_array(ModelInterface::class, class_implements($classname), true)) {
            throw new SerializerException("Mapper can work only with '" . ModelInterface::class . "', but '{$classname}' passed");
        }

        parent::__construct($classname, $mappers);
        $this->idProperty = $idProperty;
    }

    /**
     * @param ModelInterface $complex
     * @return StorageData
     * @throws SerializerException
     * @throws ReflectionException
     */
    public function serialize($complex)
    {
        $data = parent::serialize($complex);
        $id = $data[$this->idProperty];
        unset($data[$this->idProperty]);
        return new StorageData($id, $data);
    }

    /**
     * @param StorageData $data
     * @return ModelInterface|object
     * @throws ReflectionException
     * @throws SerializerException
     */
    public function deserialize($data)
    {
        if (!($data instanceof StorageData)) {
            $type = gettype($data);
            throw new SerializerException("Serialized data of model {$this->classname} should be " . StorageData::class . ", but '{$type}' type passed");
        }

        $data->body[$this->idProperty] = $data->id;
        return parent::deserialize($data->body);
    }
}