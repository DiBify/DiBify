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

    /**
     * Превращает сложный объект в простой тип (scalar, null, array)
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

        $this->guardByRegexp($complex);

        return (float) $complex;
    }

    /**
     * Превращает простой тип (scalar, null, array) в сложный (object)
     * @param mixed $data
     * @return float
     * @throws SerializerException
     */
    public function deserialize($data)
    {
        if (!is_scalar($data)) {
            $type = gettype($data);
            throw new SerializerException("Numeric expected, but '{$type}' type passed");
        }

        $this->guardByRegexp($data);

        return (float) $data;
    }

    /**
     * @param $data
     * @throws SerializerException
     */
    private function guardByRegexp($data)
    {
        if (!preg_match('~^((0)|(-?0\.\d*[1-9]+\d*)|(-?[1-9][\d]*\.\d+)|(-?[1-9][\d]*))$~u', (string) $data)) {
            throw new SerializerException("Numeric expected, but '{$data}' passed");
        }
    }

}