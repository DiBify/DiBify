<?php
/**
 * Created for DiBify.
 * Datetime: 27.10.2017 15:57
 * @author Timur Kasumov aka XAKEPEHOK
 */


namespace DiBify\DiBify\Mappers;


use DiBify\DiBify\Exceptions\SerializerException;

class IntMapper implements MapperInterface
{

    /**
     * Превращает сложный объект в простой тип (scalar, null, array)
     * @param $complex
     * @return int
     * @throws SerializerException
     */
    public function serialize($complex)
    {
        if (!is_int($complex)) {
            $type = gettype($complex);
            throw new SerializerException("Integer expected, but '{$type}' type passed");
        }
        return (int) $complex;
    }

    /**
     * Превращает простой тип (scalar, null, array) в сложный (object)
     * @param mixed $data
     * @return int
     * @throws SerializerException
     */
    public function deserialize($data)
    {
        if (!is_scalar($data)) {
            $type = gettype($data);
            throw new SerializerException("Integer expected, but '{$type}' type passed");
        }

        if (!preg_match('~^((0$)|(-?[1-9][\d]*$))~u', $data)) {
            throw new SerializerException("Integer expected, but '{$data}' passed");
        }

        return (int) $data;
    }

}