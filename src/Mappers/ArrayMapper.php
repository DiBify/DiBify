<?php
/**
 * Created for DiBify.
 * Datetime: 10.11.2017 15:17
 * @author Timur Kasumov aka XAKEPEHOK
 */

namespace DiBify\DiBify\Mappers;


use DiBify\DiBify\Exceptions\SerializerException;

class ArrayMapper implements MapperInterface
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
     * @param object $complex
     * @return array
     * @throws SerializerException
     */
    public function serialize($complex)
    {
        if (!is_array($complex)) {
            $type = gettype($complex);
            throw new SerializerException("Array expected, but '{$type}' type passed");
        }

        return array_map(function ($data) {
            return $this->mapper->serialize($data);
        }, $complex);
    }

    /**
     * @param array $data
     * @return array
     * @throws SerializerException
     */
    public function deserialize($data)
    {
        if (!is_array($data)) {
            $type = gettype($data);
            throw new SerializerException("Array expected, but '{$type}' type passed");
        }

        return array_map(function ($data) {
            return $this->mapper->deserialize($data);
        }, $data);
    }
}