<?php
/**
 * Created for DiBify
 * Date: 03.08.2023
 * @author: Timur Kasumov (XAKEPEHOK)
 */

namespace DiBify\DiBify\Mappers;

use BackedEnum;
use DiBify\DiBify\Exceptions\SerializerException;
use ValueError;

class EnumMapper implements MapperInterface
{

    private string $enumClass;
    private MapperInterface $mapper;

    public function __construct(string $enumClass, IntMapper|StringMapper $mapper)
    {
        $this->enumClass = $enumClass;
        $this->mapper = $mapper;
    }

    public function getMapper(): MapperInterface
    {
        return $this->mapper;
    }

    /**
     * @param BackedEnum $complex
     * @return int|string
     * @throws SerializerException
     */
    public function serialize($complex)
    {
        if (!($complex instanceof BackedEnum) || $complex::class !== $this->enumClass) {
            $type = gettype($complex);
            throw new SerializerException("Enum of '{$this->enumClass}' expected, but '{$type}' type passed");
        }
        return $this->mapper->serialize($complex->value);
    }


    public function deserialize($data)
    {
        if (!(is_int($data) || is_string($data))) {
            $type = gettype($data);
            throw new SerializerException("Integer or string expected for enum, but '{$type}' type passed");
        }

        try {
            /** @var BackedEnum $class */
            $class = $this->enumClass;
            return $class::from($data);
        } catch (ValueError $error) {
            throw new SerializerException("Invalid value for enum {$this->enumClass}: '{$data}'");
        }
    }

}