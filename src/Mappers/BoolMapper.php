<?php
/**
 * Created for DiBify.
 * Datetime: 02.11.2017 11:28
 * @author Timur Kasumov aka XAKEPEHOK
 */

namespace DiBify\DiBify\Mappers;


use DiBify\DiBify\Exceptions\SerializerException;

class BoolMapper implements MapperInterface
{

    /**
     * Превращает сложный объект в простой тип (scalar, null, array)
     * @param $complex
     * @return bool
     * @throws SerializerException
     */
    public function serialize($complex)
    {
        if (!is_bool($complex)) {
            $type = gettype($complex);
            throw new SerializerException("Bool expected, but '{$type}' type passed");
        }
        return (bool) $complex;
    }

    /**
     * Превращает простой тип (scalar, null, array) в сложный (object)
     * @param mixed $data
     * @return bool
     * @throws SerializerException
     */
    public function deserialize($data)
    {
        if (!is_scalar($data)) {
            $type = gettype($data);
            throw new SerializerException("Bool expected, but '{$type}' type passed");
        }

        return (bool) $data;
    }

}