<?php
/**
 * Created for DiBify
 * Date: 03.01.2020
 * @author Timur Kasumov (XAKEPEHOK)
 */

namespace DiBify\DiBify\Mappers;

use DiBify\DiBify\Pool\FloatPool;
use DiBify\DiBify\Pool\IntPool;

class PoolMapperTest extends MapperTestCase
{

    public static function serializeDataProvider(): array
    {
        return [
            [new FloatPool(100.1), ['current' => 100.1, 'pool' => 0.0]],
            [new FloatPool(100.0, 10.2), ['current' => 100.0, 'pool' => 10.2]],
        ];
    }

    public static function serializeInvalidDataProvider(): array
    {
        return [
            [new IntPool(100)],
            ['hello'],
        ];
    }

    public static function deserializeDataProvider(): array
    {
        return [
            [100.1, new FloatPool(100.1)],
            [0.0, new FloatPool(0.0)],
            [['current' => 100.1, 'pool' => 0.0], new FloatPool(100.1)],
        ];
    }

    public static function deserializeInvalidDataProvider(): array
    {
        return [
            ['hello'],
            [['current' => 100.1]],
            [['pool' => 0.0]],
        ];
    }

    protected function getMapper(): MapperInterface
    {
        return new PoolMapper(
            FloatPool::class,
            new FloatMapper()
        );
    }
}
