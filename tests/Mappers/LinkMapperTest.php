<?php
/**
 * @author Timur Kasumov (aka XAKEPEHOK)
 * Datetime: 28.10.2019 2:08
 */

namespace DiBify\DiBify\Mappers;

use DiBify\DiBify\Id\Id;
use DiBify\DiBify\Model\Link;

class LinkMapperTest extends MapperTestCase
{

    public function serializeDataProvider(): array
    {
        return [
            [
                new Link('model', new Id(1)),
                ['id' => '1', 'alias' => 'model']
            ],
        ];
    }

    public function serializeInvalidDataProvider(): array
    {
        return [
            [1],
            ['string'],
            [null],
            [true],
            [[]],
            [new LinkMapper()]
        ];
    }

    public function deserializeDataProvider(): array
    {
        return [
            [
                ['id' => '1', 'alias' => 'model'],
                new Link('model', new Id(1))
            ],
        ];
    }

    public function deserializeInvalidDataProvider(): array
    {
        return [
            ['id' => '1'],
            ['alias' => 'model'],
            ['id' => '1', 'model' => null],
            ['id' => null, 'model' => 'model'],
            ['id' => null, 'model' => null],
            [1],
            ['string'],
            [null],
            [true],
            [[]],
            [new LinkMapper()]
        ];
    }

    protected function getMapper(): MapperInterface
    {
        return new LinkMapper();
    }
}
