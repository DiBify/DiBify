<?php
/**
 * Created for DiBify
 * Date: 03.08.2023
 * @author: Timur Kasumov (XAKEPEHOK)
 */

namespace DiBify\DiBify\Mappers;

use DiBify\DiBify\Mock\MockEnum_1;
use DiBify\DiBify\Mock\MockEnum_2;

class EnumMapperTest extends MapperTestCase
{

    public function testGetNestedMapper(): void
    {
        $int = new EnumMapper(MockEnum_1::class, IntMapper::getInstance());
        $string = new EnumMapper(MockEnum_2::class, StringMapper::getInstance());
        $this->assertSame(IntMapper::getInstance(), $int->getMapper());
        $this->assertSame(StringMapper::getInstance(), $string->getMapper());
    }

    public function serializeDataProvider(): array
    {
        return [
            [MockEnum_1::key_1, 'value_1'],
            [MockEnum_1::key_2, 'value_2'],
        ];
    }

    public function serializeInvalidDataProvider(): array
    {
        return [
            [MockEnum_2::key_1],
            [MockEnum_2::key_2],
            [1],
            ['value_3'],
            [null],
            [[]],
            [true],
        ];
    }

    public function deserializeDataProvider(): array
    {
        return [
            ['value_1', MockEnum_1::key_1],
            ['value_2', MockEnum_1::key_2],
        ];
    }

    public function deserializeInvalidDataProvider(): array
    {
        return [
            ['value_3'],
            [123],
            [false],
            [null],
            [[]],
        ];
    }

    protected function getMapper(): MapperInterface
    {
        return new EnumMapper(MockEnum_1::class, StringMapper::getInstance());
    }
}
