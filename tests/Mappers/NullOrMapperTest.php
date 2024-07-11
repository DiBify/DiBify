<?php
/**
 * @author Timur Kasumov (aka XAKEPEHOK)
 * Datetime: 28.10.2019 0:55
 */

namespace DiBify\DiBify\Mappers;

class NullOrMapperTest extends MapperTestCase
{

    public static function serializeDataProvider(): array
    {
        return [
            [null, null],
            [1, 1],
        ];
    }

    public static function serializeInvalidDataProvider(): array
    {
        return [
            ['string'],
            [false],
            [[]],
        ];
    }

    public static function deserializeDataProvider(): array
    {
        return [
            [null, null],
            [1, 1],
        ];
    }

    public static function deserializeInvalidDataProvider(): array
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
