<?php
/**
 * @author Timur Kasumov (aka XAKEPEHOK)
 * Datetime: 28.10.2019 1:22
 */

namespace DiBify\DiBify\Mappers;

use DateTime;
use DateTimeImmutable;
use DateTimeInterface;

class DateTimeMapperTest extends MapperTestCase
{

    public function serializeDataProvider(): array
    {
        return [
            [$this->validDatetime('2019-10-28 01:24:15'), '2019-10-28 01:24:15'],
        ];
    }

    public function serializeInvalidDataProvider(): array
    {
        return [
            ['2019-10-28 01:24:15'],
            [null],
            [1],
            [[]],
            [$this->invalidDatetime('2019-10-28 01:24:15')],
        ];
    }

    public function deserializeDataProvider(): array
    {
        return [
            ['2019-10-28 01:24:15', $this->validDatetime('2019-10-28 01:24:15')],
        ];
    }

    public function deserializeInvalidDataProvider(): array
    {
        return [
            ['28-10-2019 01:24:15'],
            [null],
            [1],
            [[]],
            [$this->invalidDatetime('2019-10-28 01:24:15')],
        ];
    }

    protected function validDatetime(string $dateTime): DateTimeInterface
    {
        return new DateTime($dateTime);
    }

    protected function invalidDatetime(string $dateTime): DateTimeInterface
    {
        return new DateTimeImmutable($dateTime);
    }

    protected function getMapper(): MapperInterface
    {
        return new DateTimeMapper();
    }
}
