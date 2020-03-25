<?php
/**
 * @author Timur Kasumov (aka XAKEPEHOK)
 * Datetime: 07.09.2019 17:18
 */

namespace DiBify\DiBify\Mappers;


use DiBify\DiBify\Exceptions\SerializerException;

class NullOrMapper implements MapperInterface
{

    /**
     * @var MapperInterface
     */
    private $mapper;

    public function __construct(MapperInterface $mapper)
    {
        $this->mapper = $mapper;
    }

    /**
     * Превращает сложный объект в простой тип (scalar, null, array)
     * @param $complex
     * @return mixed
     * @throws SerializerException
     */
    public function serialize($complex)
    {
        if (is_null($complex)) {
            return $complex;
        }
        return $this->mapper->serialize($complex);
    }

    /**
     * Превращает простой тип (scalar, null, array) в сложный (object)
     * @param mixed $data
     * @return mixed
     * @throws SerializerException
     */
    public function deserialize($data)
    {
        if ($data === null) {
            return null;
        }
        return $this->mapper->deserialize($data);
    }
}