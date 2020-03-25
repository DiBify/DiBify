<?php
/**
 * Created for DiBify.
 * Datetime: 27.10.2017 15:57
 * @author Timur Kasumov aka XAKEPEHOK
 */

namespace DiBify\DiBify\Mappers;


use DiBify\DiBify\Exceptions\SerializerException;
use DiBify\DiBify\Id\Id;

class IdMapper implements MapperInterface
{

    /**
     * @param Id $complex
     * @return mixed
     * @throws SerializerException
     */
    public function serialize($complex)
    {
        if (!$complex->isAssigned()) {
            throw new SerializerException("Id should has assigned permanent value");
        }
        return (string) $complex;
    }

    /**
     * @inheritDoc
     */
    public function deserialize($data)
    {
        if (!is_string($data) && !is_int($data) && !is_float($data)) {
            throw new SerializerException("Id expected, but '" . gettype($data) . "' passed");
        }
        return new Id($data);
    }
}