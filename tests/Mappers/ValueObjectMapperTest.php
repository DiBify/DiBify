<?php
/**
 * Created for dibify
 * Date: 22.04.2020
 * @author Timur Kasumov (XAKEPEHOK)
 */

namespace DiBify\DiBify\Mappers;

use DateTime;
use DiBify\DiBify\Mock\ValueObject;

class ValueObjectMapperTest extends MapperTestCase
{

    public static function serializeDataProvider(): array
    {
        return [
            [new ValueObject('qwerty'), 'qwerty']
        ];
    }

    public static function serializeInvalidDataProvider(): array
    {
        return [
            [new DateTime()]
        ];
    }

    public static function deserializeDataProvider(): array
    {
        return [
            ['qwerty', new ValueObject('qwerty')]
        ];
    }

    public static function deserializeInvalidDataProvider(): array
    {
        return [
            [null],
            [[]],
        ];
    }

    protected function getMapper(): MapperInterface
    {
        return new ValueObjectMapper(ValueObject::class, 'value', new StringMapper());
    }
}
