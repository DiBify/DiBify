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
     * Convert complex data (like object) to simpe data (scalar, null, array)
     * @param $complex
     * @return mixed
     * @throws SerializerException
     */
    public function serialize($complex);

    /**
     * Convert simple data (scalar, null, array) into complex data (like object)
     * @param mixed $data
     * @return mixed
     * @throws SerializerException
     */
    public function deserialize($data);

}