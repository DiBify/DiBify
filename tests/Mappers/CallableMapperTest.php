<?php
/**
 * @author Timur Kasumov (aka XAKEPEHOK)
 * Datetime: 28.10.2019 1:36
 */

namespace DiBify\DiBify\Mappers;

use DiBify\DiBify\Exceptions\SerializerException;

class CallableMapperTest extends MapperTestCase
{

    public static function serializeDataProvider(): array
    {
        return [
            [1, 1],
        ];
    }

    public static function serializeInvalidDataProvider(): array
    {
        return [
            ['invalid'],
        ];
    }

    public static function deserializeDataProvider(): array
    {
        return static::serializeDataProvider();
    }

    public static function deserializeInvalidDataProvider(): array
    {
        return static::serializeInvalidDataProvider();
    }

    protected function getMapper(): MapperInterface
    {
        $function = function ($value) {
            if ($value === 'invalid') {
                throw new SerializerException('Invalid data');
            }
            return $value;
        };

        return new CallableMapper($function, $function);
    }
}
