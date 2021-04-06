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

    use SharedMapperTrait;

    /**
     * Convert complex data (like object) to simple data (scalar, null, array)
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
     * Convert simple data (scalar, null, array) into complex data (like object)
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