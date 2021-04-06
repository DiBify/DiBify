<?php
/**
 * @author Timur Kasumov (aka XAKEPEHOK)
 * Datetime: 25.10.2019 13:35
 */

namespace DiBify\DiBify\Mappers\Components;


use DiBify\DiBify\Exceptions\SerializerException;
use DiBify\DiBify\Mappers\MapperInterface;

class UnionRule
{

    private MapperInterface $mapper;

    /** @var callable */
    private $serialize;

    /** @var callable */
    private $deserialize;

    public function __construct(MapperInterface $mapper, callable $serialize, callable $deserialize)
    {
        $this->mapper = $mapper;
        $this->serialize = $serialize;
        $this->deserialize = $deserialize;
    }

    /**
     * @param object $complex
     * @return array
     * @throws SerializerException
     */
    public function serialize($complex)
    {
        $serialized = $this->mapper->serialize($complex);
        return ($this->serialize)($complex, $serialized);
    }

    /**
     * @param mixed $serialized
     * @return object
     * @throws SerializerException
     */
    public function deserialize($serialized)
    {
        $handled = ($this->deserialize)($serialized);
        return $this->mapper->deserialize($handled);
    }

}