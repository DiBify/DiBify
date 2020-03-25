<?php
/**
 * Created for DiBify
 * Datetime: 01.10.2019 12:25
 * @author Timur Kasumov aka XAKEPEHOK
 */

namespace DiBify\DiBify\Mappers;


use DiBify\DiBify\Exceptions\SerializerException;
use Exception;

class CallableMapper implements MapperInterface
{

    /**
     * @var callable
     */
    private $serialize;
    /**
     * @var callable
     */
    private $deserialize;

    public function __construct(callable $serialize, callable $deserialize)
    {
        $this->serialize = $serialize;
        $this->deserialize = $deserialize;
    }

    /**
     * Превращает сложный объект в простой тип (scalar, null, array)
     * @param $complex
     * @return mixed
     * @throws SerializerException
     */
    public function serialize($complex)
    {
        try {
            return ($this->serialize)($complex);
        } catch (Exception $exception) {
            throw new SerializerException(
                $exception->getMessage(),
                $exception->getCode(),
                $exception
            );
        }
    }

    /**
     * Превращает простой тип (scalar, null, array) в сложный (object)
     * @param mixed $data
     * @return mixed
     * @throws SerializerException
     */
    public function deserialize($data)
    {
        try {
            return ($this->deserialize)($data);
        } catch (Exception $exception) {
            throw new SerializerException(
                $exception->getMessage(),
                $exception->getCode(),
                $exception
            );
        }
    }
}