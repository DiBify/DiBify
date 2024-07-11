<?php
/**
 * @author Timur Kasumov (aka XAKEPEHOK)
 * Datetime: 25.10.2019 17:29
 */

namespace DiBify\DiBify\Mappers;


class FloatMapperTest extends MapperTestCase
{

    public static function serializeDataProvider(): array
    {
        return [
            [0, 0.0],
            [1, 1.0],
            [-1, -1.0],
            [100, 100.0],
            [0.1, 0.1],
            [1.1, 1.1],
            [-1.1, -1.1],
            [100.001, 100.001],
        ];
    }

    public static function serializeInvalidDataProvider(): array
    {
        return [
            ['qwerty'],
            [[]],
            [null],
        ];
    }

    public static function deserializeDataProvider(): array
    {
        return [
            ['0', 0.0],
            ['1', 1.0],
            ['-1', -1.0],
            ['100', 100.0],
            ['0.1', 0.1],
            ['1.1', 1.1],
            ['-1.1', -1.1],
            ['100.001', 100.001],
        ];
    }

    public static function deserializeInvalidDataProvider(): array
    {
        return static::serializeInvalidDataProvider();
    }

    protected function getMapper(): MapperInterface
    {
        return new FloatMapper();
    }
}
