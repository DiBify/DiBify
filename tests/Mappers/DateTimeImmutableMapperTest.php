<?php
/**
 * @author Timur Kasumov (aka XAKEPEHOK)
 * Datetime: 28.10.2019 1:31
 */

namespace DiBify\DiBify\Mappers;

use DateTime;
use DateTimeImmutable;
use DateTimeInterface;

class DateTimeImmutableMapperTest extends DateTimeMapperTest
{

    protected function validDatetime(string $dateTime): DateTimeInterface
    {
        return new DateTimeImmutable($dateTime);
    }

    protected function invalidDatetime(string $dateTime): DateTimeInterface
    {
        return new DateTime($dateTime);
    }

    protected function getMapper(): MapperInterface
    {
        return new DateTimeImmutableMapper();
    }

}
