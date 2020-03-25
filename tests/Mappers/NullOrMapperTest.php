<?php
/**
 * @author Timur Kasumov (aka XAKEPEHOK)
 * Datetime: 28.10.2019 0:55
 */

namespace DiBify\DiBify\Mappers;

class NullOrMapperTest extends MapperTestCase
{

    public function serializeDataProvider(): array
    {
        return [
            [null, null],
            [1, 1],
        ];
    }

    public function serializeInvalidDataProvider(): array
    {
        return [
            ['string'],
            [false],
            [[]],
        ];
    }

    public function deserializeDataProvider(): array
    {
        return [
            [null, null],
            [1, 1],
        ];
    }

    public function deserializeInvalidDataProvider(): array
    {
        return [
            ['string'],
            [false],
            [[]],
        ];
    }

    protected function getMapper(): MapperInterface
    {
        return new NullOrMapper(new IntMapper());
    }
}
