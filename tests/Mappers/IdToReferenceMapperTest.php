<?php
/**
 * Created for dibify
 * Date: 28.03.2020
 * @author Timur Kasumov (XAKEPEHOK)
 */

namespace DiBify\DiBify\Mappers;


use DiBify\DiBify\Id\Id;
use DiBify\DiBify\Model\Reference;

class IdToReferenceMapperTest extends MapperTestCase
{

    public function serializeDataProvider(): array
    {
        return [
            [Reference::create('model', 100500), '100500'],
        ];
    }

    public function serializeInvalidDataProvider(): array
    {
        return [
            [Reference::create('model', new Id())],
            [Reference::create('alias', new Id())],
        ];
    }

    public function deserializeDataProvider(): array
    {
        return [
            ['100500', Reference::create('model', 100500)],
            [100500, Reference::create('model', 100500)],
        ];
    }

    public function deserializeInvalidDataProvider(): array
    {
        return [
            [null],
            [''],
        ];
    }

    protected function getMapper(): MapperInterface
    {
        return new IdToReferenceMapper('model');
    }
}
