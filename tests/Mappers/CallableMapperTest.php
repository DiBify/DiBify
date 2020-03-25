<?php
/**
 * @author Timur Kasumov (aka XAKEPEHOK)
 * Datetime: 28.10.2019 1:36
 */

namespace DiBify\DiBify\Mappers;

use DiBify\DiBify\Exceptions\SerializerException;

class CallableMapperTest extends MapperTestCase
{

    public function serializeDataProvider(): array
    {
        return [
            [1, 1],
        ];
    }

    public function serializeInvalidDataProvider(): array
    {
        return [
            ['invalid'],
        ];
    }

    public function deserializeDataProvider(): array
    {
        return $this->serializeDataProvider();
    }

    public function deserializeInvalidDataProvider(): array
    {
        return $this->serializeInvalidDataProvider();
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
