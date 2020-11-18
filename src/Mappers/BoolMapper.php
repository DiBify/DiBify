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

    use SharedMapperTrait;

    /**
     * Convert complex data (like object) to simpe data (scalar, null, array)
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
     * Convert simple data (scalar, null, array) into complex data (like object)
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