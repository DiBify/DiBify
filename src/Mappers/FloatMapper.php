<?php
/**
 * Created for DiBify.
 * Datetime: 27.10.2017 15:57
 * @author Timur Kasumov aka XAKEPEHOK
 */


namespace DiBify\DiBify\Mappers;


use DiBify\DiBify\Exceptions\SerializerException;

class FloatMapper implements MapperInterface
{

    use SharedMapperTrait;

    /**
     * Convert complex data (like object) to simple data (scalar, null, array)
     * @param $complex
     * @return float
     * @throws SerializerException
     */
    public function serialize($complex)
    {
        if (!is_numeric($complex)) {
            $type = gettype($complex);
            throw new SerializerException("Numeric expected, but '{$type}' type passed");
        }
        return (float) $complex;
    }

    /**
     * Convert simple data (scalar, null, array) into complex data (like object)
     * @param mixed $data
     * @return float
     * @throws SerializerException
     */
    public function deserialize($data)
    {
        if (!is_scalar($data) || !is_numeric($data)) {
            $type = gettype($data);
            throw new SerializerException("Numeric expected, but '{$type}' type passed");
        }

        return (float) $data;
    }

}