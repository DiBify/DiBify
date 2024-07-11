<?php
/**
 * @author Timur Kasumov (aka XAKEPEHOK)
 * Datetime: 28.10.2019 2:08
 */

namespace DiBify\DiBify\Mappers;

use DiBify\DiBify\Id\Id;
use DiBify\DiBify\Model\Reference;

class ReferenceMapperTest extends MapperTestCase
{

    public static function serializeDataProvider(): array
    {
        return [
            [
                Reference::create('model', new Id(1)),
                ['alias' => 'model', 'id' => '1']
            ],
        ];
    }

    public static function serializeInvalidDataProvider(): array
    {
        return [
            [1],
            ['string'],
            [null],
            [true],
            [[]],
            [new ReferenceMapper()]
        ];
    }

    public static function deserializeDataProvider(): array
    {
        return [
            [
                ['id' => '1', 'alias' => 'model'],
                Reference::create('model', new Id(1))
            ],
        ];
    }

    public static function deserializeInvalidDataProvider(): array
    {
        return [
            [['id' => '1']],
            [['alias' => 'model']],
            [['id' => '1', 'alias' => null]],
            [['id' => null, 'alias' => 'model']],
            [['id' => null, 'alias' => null]],
            [1],
            ['string'],
            [null],
            [true],
            [[]],
            [new ReferenceMapper()]
        ];
    }

    protected function getMapper(): MapperInterface
    {
        return new ReferenceMapper();
    }
}
