<?php
/**
 * @author Timur Kasumov (aka XAKEPEHOK)
 * Datetime: 25.10.2019 17:29
 */

namespace DiBify\DiBify\Mappers;


class IntMapperTest extends MapperTestCase
{

    public static function serializeDataProvider(): array
    {
        return [
            [0, 0],
            [1, 1],
            [-1, -1],
            [100, 100],
        ];
    }

    public static function serializeInvalidDataProvider(): array
    {
        return [
            [0.1],
            [1.0],
            ['0.1'],
            ['000'],
            ['-0'],
            ['qwerty'],
            ['+1'],
            [[]],
            [null],
        ];
    }

    public static function deserializeDataProvider(): array
    {
        return [
            [0, 0],
            [1, 1],
            [-1, -1],
            [100, 100],
            ['0', 0],
            ['1', 1],
            ['-1', -1],
            ['100', 100],
        ];
    }

    public static function deserializeInvalidDataProvider(): array
    {
        return [
            [0.1],
            ['0.1'],
            ['000'],
            ['-0'],
            ['qwerty'],
            ['+1'],
            [[]],
            [null],
        ];
    }

    protected function getMapper(): MapperInterface
    {
        return new IntMapper();
    }
}
