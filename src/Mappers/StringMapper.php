<?php
/**
 * Created for DiBify.
 * Datetime: 27.10.2017 15:57
 * @author Timur Kasumov aka XAKEPEHOK
 */


namespace DiBify\DiBify\Mappers;


use DiBify\DiBify\Exceptions\SerializerException;

class StringMapper implements MapperInterface
{

    /**
     * Превращает сложный объект в простой тип (scalar, null, array)
     * @param $complex
     * @return string
     * @throws SerializerException
     */
    public function serialize($complex)
    {
        if (!is_string($complex)) {
            $type = gettype($complex);
            throw new SerializerException("String expected, but '{$type}' type passed");
        }
        return (string) $complex;
    }

    /**
     * Превращает простой тип (scalar, null, array) в сложный (object)
     * @param mixed $data
     * @return string
     * @throws SerializerException
     */
    public function deserialize($data)
    {
        if (!is_scalar($data)) {
            $type = gettype($data);
            throw new SerializerException("String expected, but '{$type}' type passed");
        }

        return (string) $data;
    }
}