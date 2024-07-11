<?php
/**
 * @author Timur Kasumov (aka XAKEPEHOK)
 * Datetime: 28.10.2019 2:00
 */

namespace DiBify\DiBify\Mappers;

use DiBify\DiBify\Mock\TestModel_1;

class ObjectMapperTest extends MapperTestCase
{

    public static function serializeDataProvider(): array
    {
        return [
            [new TestModel_1(1, 2, '3'), ['id' => '1', 'otherId' => '2', 'custom' => '3']],
            [new TestModel_1(1, 2, null), ['id' => '1', 'otherId' => '2', 'custom' => null]],
        ];
    }

    public static function serializeInvalidDataProvider(): array
    {
        return [
            [null],
            [1],
            ['string'],
            [[]],
            [new IdMapper()],
        ];
    }

    public static function deserializeDataProvider(): array
    {
        return [
            [['id' => '1', 'otherId' => '2', 'custom' => '3'], new TestModel_1(1, 2, '3')],
            [['id' => '1', 'otherId' => '2', 'custom' => null], new TestModel_1(1, 2, null)],
            [['id' => '1', 'otherId' => '2'], new TestModel_1(1, 2, null)],
        ];
    }

    public static function deserializeInvalidDataProvider(): array
    {
        return [
            [null],
            [1],
            ['string'],
            [[]],
            [new IdMapper()],
            [['id' => '1', 'otherId' => null, 'custom' => null]],
            [['id' => null, 'otherId' => null, 'custom' => null]],
        ];
    }

    protected function getMapper(): MapperInterface
    {
        return new ObjectMapper(
            TestModel_1::class,
            [
                'id' => new IdMapper(),
                'otherId' => new IdMapper(),
                'custom' => new NullOrMapper(new StringMapper()),
            ]
        );
    }
}
