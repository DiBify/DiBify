<?php
/**
 * @author Timur Kasumov (aka XAKEPEHOK)
 * Datetime: 25.10.2019 17:29
 */

namespace DiBify\DiBify\Mappers;


class FloatMapperTest extends MapperTestCase
{

    public function serializeDataProvider(): array
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

    public function serializeInvalidDataProvider(): array
    {
        return [
            ['000'],
            ['-0'],
            ['qwerty'],
            ['+1'],
            ['000'],
            ['-0'],
            ['qwerty'],
            ['-0.0'],
            ['002.00'],
            ['-002.00'],
            ['2.'],
            ['.2'],
            [[]],
            [null],
        ];
    }

    public function deserializeDataProvider(): array
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

    public function deserializeInvalidDataProvider(): array
    {
        return $this->serializeInvalidDataProvider();
    }

    protected function getMapper(): MapperInterface
    {
        return new FloatMapper();
    }
}
