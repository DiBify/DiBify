<?php
/**
 * Created for DiBify
 * Date: 02.01.2020
 * @author Timur Kasumov (XAKEPEHOK)
 */

namespace DiBify\DiBify\Mappers;


use DiBify\DiBify\Exceptions\SerializerException;
use DiBify\DiBify\Pool\PoolInterface;

class PoolMapper implements MapperInterface
{

    /** @var PoolInterface[]  */
    protected static array $pools = [];

    private string $poolClass;

    private MapperInterface $mapper;

    public function __construct(string $poolClass, MapperInterface $mapper)
    {
        $this->poolClass = $poolClass;
        $this->mapper = $mapper;
    }

    /**
     * @param PoolInterface $complex
     * @return array|mixed
     * @throws SerializerException
     */
    public function serialize($complex)
    {
        if (!is_a($complex, $this->poolClass)) {
            $type = gettype($complex);
            throw new SerializerException("'{$this->poolClass}' expected, but '{$type}' type passed");
        }

        self::$pools[] = $complex;

        return [
            'current' => $this->mapper->serialize($complex->getCurrent()),
            'pool' => (new NullOrMapper($this->mapper))->serialize($complex->getPool())
        ];
    }

    /**
     * @inheritDoc
     */
    public function deserialize($data)
    {
        if (is_array($data)) {
            if (isset($data['current']) && isset($data['pool'])) {
                $data = $data['current'] + $data['pool'];
            } else {
                throw new SerializerException("Pool expected, but array passed");
            }
        }

        $current = $this->mapper->deserialize($data);

        $class = $this->poolClass;

        /** @var PoolInterface $pool */
        return new $class($current);
    }

    public static function merge(): void
    {
        foreach (self::$pools as $pool) {
            $pool->merge();
        }
        self::$pools = [];
    }

    public static function freeUpMemory(): void
    {
        self::$pools = [];
    }

}