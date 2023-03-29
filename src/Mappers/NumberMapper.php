<?php
/**
 * Created for DiBify.
 * Datetime: 29.03.2023 22:37
 * @author Timur Kasumov aka XAKEPEHOK
 */

namespace DiBify\DiBify\Mappers;

use DiBify\DiBify\Exceptions\SerializerException;

class NumberMapper implements MapperInterface
{

    use SharedMapperTrait;

    /**
     * Convert complex data (like object) to simple data (scalar, null, array)
     * @param $complex
     * @return int|float
     * @throws SerializerException
     */
    public function serialize($complex)
    {
        if (!is_int($complex) && !is_float($complex)) {
            $type = gettype($complex);
            throw new SerializerException("Integer or float expected, but '{$type}' type passed");
        }
        return $this->handle($complex);
    }

    /**
     * Convert simple data (scalar, null, array) into complex data (like object)
     * @param mixed $data
     * @return int|float
     * @throws SerializerException
     */
    public function deserialize($data)
    {
        if (!is_scalar($data)) {
            $type = gettype($data);
            throw new SerializerException("Integer or float expected, but '{$type}' type passed");
        }

        if (is_integer($data)) {
            return $data;
        }

        if (is_string($data) && !preg_match('~^((0)|(-?0\.\d*[1-9]+\d*)|(-?[1-9][\d]*\.\d+)|(-?[1-9][\d]*))$~u', (string) $data)) {
            throw new SerializerException("Integer or float expected, but '{$data}' passed");
        }

        return $this->handle(floatval($data));
    }

    private function handle(int|float $value): int|float
    {
        return round($value) != $value ? $value : intval(round($value));
    }
}