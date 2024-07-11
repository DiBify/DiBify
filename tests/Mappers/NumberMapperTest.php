<?php

namespace DiBify\DiBify\Mappers;

class NumberMapperTest extends MapperTestCase
{

    public static function serializeDataProvider(): array
    {
        return [
            [1, 1],
            [0, 0],
            [1.1, 1.1],
            [-1, -1],
            [-1.1, -1.1],
        ];
    }

    public static function serializeInvalidDataProvider(): array
    {
        return [
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
            ['1.1', 1.1],
            ['-1.1', -1.1],
            ['100.1', 100.1],
        ];
    }

    public static function deserializeInvalidDataProvider(): array
    {
        return [
            ['000'],
            ['-0'],
            ['0.0'],
            ['qwerty'],
            ['+1'],
            [[]],
            [null],
        ];
    }

    protected function getMapper(): MapperInterface
    {
        return NumberMapper::getInstance();
    }
}
