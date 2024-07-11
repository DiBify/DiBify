<?php
/**
 * @author Timur Kasumov (aka XAKEPEHOK)
 * Datetime: 27.10.2019 22:39
 */

namespace DiBify\DiBify\Mappers;


class StringMapperTest extends MapperTestCase
{

    public static function serializeDataProvider(): array
    {
        return [
            'string' => ['string', 'string'],
        ];
    }

    public static function serializeInvalidDataProvider(): array
    {
        return [
            [null],
            [new StringMapper()],
            [[]],
        ];
    }

    public static function deserializeDataProvider(): array
    {
        return [
            'string' => ['string', 'string'],
            '0' => [0, '0'],
            '1' => [1, '1'],
            '1.1' => [1.1, '1.1'],
            'true' => [true, '1'],
            'false' => [false, ''],
        ];
    }

    public static function deserializeInvalidDataProvider(): array
    {
        return static::serializeInvalidDataProvider();
    }

    protected function getMapper(): MapperInterface
    {
        return new StringMapper();
    }
}
