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

    public function testMerge(): void
    {
        $pool = new FloatPool(100, 10);
        $mapper = new class(FloatPool::class, new FloatMapper()) extends PoolMapper {
            public static function getPoolsArray(): array
            {
                return self::$pools;
            }
        };

        $this->assertNotEmpty($mapper::getPoolsArray());
        PoolMapper::freeUpMemory();

        $this->assertEmpty($mapper::getPoolsArray());
        $this->assertSame(100.0, $pool->getCurrent());
        $this->assertSame(10.0, $pool->getPool());
        $this->assertSame(110.0, $pool->getResult());

        $mapper->serialize($pool);
        $this->assertSame([$pool], $mapper::getPoolsArray());
        $this->assertSame(100.0, $pool->getCurrent());
        $this->assertSame(10.0, $pool->getPool());
        $this->assertSame(110.0, $pool->getResult());

        $mapper::merge();
        $this->assertEmpty($mapper::getPoolsArray());
        $this->assertSame(110.0, $pool->getCurrent());
        $this->assertSame(0.0, $pool->getPool());
        $this->assertSame(110.0, $pool->getResult());
    }
}
