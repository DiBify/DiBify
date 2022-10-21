<?php
/**
 * Created for DiBify.
 * Datetime: 02.08.2018 15:14
 * @author Timur Kasumov aka XAKEPEHOK
 */

namespace DiBify\DiBify\Mappers;


use DiBify\DiBify\Exceptions\SerializerException;
use DiBify\DiBify\Model\Reference;

class ReferenceMapper extends ObjectMapper
{

    private bool $eager;

    private static self $instanceEager;
    private static self $instanceLazy;

    public function __construct(bool $eager = false)
    {
        $this->eager = $eager;
        parent::__construct(Reference::class, [
            'alias' => StringMapper::getInstance(),
            'id' => IdMapper::getInstance(),
        ]);
    }

    public function deserialize($data)
    {
        if (!is_array($data) || !isset($data['alias']) || !isset($data['id'])) {
            throw new SerializerException("Serialized data of Reference are invalid");
        }

        $reference = Reference::create($data['alias'], $data['id']);

        if ($this->eager) {
            Reference::preload($reference);
        }

        return $reference;
    }

    public static function getInstanceEager(): self
    {
        if (!isset(static::$instanceEager)) {
            static::$instanceEager = new static(true);
        }
        return static::$instanceEager;
    }

    public static function getInstanceLazy(): self
    {
        if (!isset(static::$instanceLazy)) {
            static::$instanceLazy = new static(false);
        }
        return static::$instanceLazy;
    }

}