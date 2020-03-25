<?php
/**
 * @author Timur Kasumov (aka XAKEPEHOK)
 * Datetime: 28.10.2019 1:40
 */

namespace DiBify\DiBify\Mappers;

class ArrayMapperTest extends MapperTestCase
{

    public function serializeDataProvider(): array
    {
        return [
            [[1, 2, 3], [1, 2, 3]],
            [[], []],
        ];
    }

    public function serializeInvalidDataProvider(): array
    {
        return [
            [null],
            [1],
            ['1'],
            [true],
            [['string']],
        ];
    }

    public function deserializeDataProvider(): array
    {
        return $this->serializeDataProvider();
    }

    public function deserializeInvalidDataProvider(): array
    {
        return $this->serializeInvalidDataProvider();
    }

    protected function getMapper(): MapperInterface
    {
        return new ArrayMapper(
            new IntMapper()
        );
    }
}
