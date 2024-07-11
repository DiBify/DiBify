<?php
/**
 * @author Timur Kasumov (aka XAKEPEHOK)
 * Datetime: 28.10.2019 0:42
 */

namespace DiBify\DiBify\Mappers;

class BoolMapperTest extends MapperTestCase
{

    public static function serializeDataProvider(): array
    {
        return [
            [true, true],
            [false, false],
        ];
    }

    public static function serializeInvalidDataProvider(): array
    {
        return [
            [null],
            [''],
            [0],
            [1],
            ['0'],
            ['1'],
            ['false'],
            ['true'],
        ];
    }

    public static function deserializeDataProvider(): array
    {
        return [
            [true, true],
            [false, false],
            [1, true],
            [0, false],
            ['1', true],
            [0, false],
        ];
    }

    public static function deserializeInvalidDataProvider(): array
    {
        return [
            [null],
            [[]],
            [new BoolMapper()],
        ];
    }

    protected function getMapper(): MapperInterface
    {
        return new BoolMapper();
    }
}
