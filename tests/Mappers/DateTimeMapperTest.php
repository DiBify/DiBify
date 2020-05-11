<?php
/**
 * @author Timur Kasumov (aka XAKEPEHOK)
 * Datetime: 28.10.2019 1:22
 */

namespace DiBify\DiBify\Mappers;

use DateTimeImmutable;

class DateTimeMapperTest extends MapperTestCase
{

    public function serializeDataProvider(): array
    {
        return [
            [new DateTimeImmutable("@1587420198"), 1587420198],
        ];
    }

    public function serializeInvalidDataProvider(): array
    {
        return [
            ["1587420198"],
            ['2019-10-28 01:24:15'],
            [null],
            [1],
            [[]],
        ];
    }

    public function deserializeDataProvider(): array
    {
        return [
            ['1587420198', new DateTimeImmutable("@1587420198")],
            [1587420198, new DateTimeImmutable("@1587420198")],
        ];
    }

    public function deserializeInvalidDataProvider(): array
    {
        return [
            [null],
            [[]],
        ];
    }

    protected function getMapper(): MapperInterface
    {
        return new DateTimeMapper();
    }
}
