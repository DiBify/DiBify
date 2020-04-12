<?php
/**
 * Created for dibify
 * Date: 12.04.2020
 * @author Timur Kasumov (XAKEPEHOK)
 */

namespace DiBify\DiBify\Mappers;

use DiBify\DiBify\Exceptions\SerializerException;
use DiBify\DiBify\Mock\TestModel_1;
use DiBify\DiBify\Repository\Storage\StorageData;

class ModelMapperTest extends MapperTestCase
{

    public function testConstructNotModelInterface()
    {
        $this->expectException(SerializerException::class);
        new ModelMapper(
            MapperTestCase::class,
            [
                'id' => new IdMapper(),
                'otherId' => new IdMapper(),
                'custom' => new NullOrMapper(new StringMapper()),
            ]
        );
    }

    public function serializeDataProvider(): array
    {
        return [
            [new TestModel_1(1, 2, '3'), new StorageData('1', ['otherId' => '2', 'custom' => '3'])],
            [new TestModel_1(1, 2, null), new StorageData('1', ['otherId' => '2', 'custom' => null])],
        ];
    }

    public function serializeInvalidDataProvider(): array
    {
        return [
            [null],
            [1],
            ['string'],
            [[]],
            [new IdMapper()],
        ];
    }

    public function deserializeDataProvider(): array
    {
        return [
            [new StorageData('1', ['otherId' => '2', 'custom' => '3']), new TestModel_1(1, 2, '3')],
            [new StorageData('1', ['otherId' => '2', 'custom' => null]), new TestModel_1(1, 2, null)],
            [new StorageData('1', ['otherId' => '2']), new TestModel_1(1, 2, null)],
        ];
    }

    public function deserializeInvalidDataProvider(): array
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
        return new ModelMapper(
            TestModel_1::class,
            [
                'id' => new IdMapper(),
                'otherId' => new IdMapper(),
                'custom' => new NullOrMapper(new StringMapper()),
            ]
        );
    }
}
