<?php
/**
 * Created for DiBify.
 * Datetime: 27.10.2017 15:57
 * @author Timur Kasumov aka XAKEPEHOK
 */


namespace DiBify\DiBify\Mappers;


use DiBify\DiBify\Exceptions\SerializerException;

interface MapperInterface
{

    /**
     * Превращает сложный объект в простой тип (scalar, null, array)
     * @param $complex
     * @return mixed
     * @throws SerializerException
     */
    public function serialize($complex);

    /**
     * Превращает простой тип (scalar, null, array) в сложный (object)
     * @param mixed $data
     * @return mixed
     * @throws SerializerException
     */
    public function deserialize($data);

}