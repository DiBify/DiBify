<?php
/**
 * Created for DiBify.
 * Datetime: 31.10.2017 12:03
 * @author Timur Kasumov aka XAKEPEHOK
 */

namespace DiBify\DiBify\Mappers;


use DateTimeImmutable;

class DateTimeImmutableMapper extends DateTimeMapper
{

    /**
     * @return string
     */
    protected function classname(): string
    {
        return DateTimeImmutable::class;
    }

}