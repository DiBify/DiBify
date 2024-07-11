<?php
/**
 * @author Timur Kasumov (aka XAKEPEHOK)
 * Datetime: 28.10.2019 1:40
 */

namespace DiBify\DiBify\Mappers;

class ArrayMapperTest extends MapperTestCase
{

    public static function serializeDataProvider(): array
    {
        return [
            [[1, 2, 3], [1, 2, 3]],
            [['a' => 1, 'b' => 2, 'c' => 3], ['a' => 1, 'b' => 2, 'c' => 3]],
            [[], []],
        ];
    }

    public static function serializeInvalidDataProvider(): array
    {
        return [
            [null],
            [1],
            ['1'],
            [true],
            [['string']],
        ];
    }

    public static function deserializeDataProvider(): array
    {
        return static::serializeDataProvider();
    }

    public static function deserializeInvalidDataProvider(): array
    {
        return static::serializeInvalidDataProvider();
    }

    protected function getMapper(): MapperInterface
    {
        return new ArrayMapper(
            new IntMapper()
        );
    }
}
