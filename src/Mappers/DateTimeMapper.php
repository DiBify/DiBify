<?php
/**
 * Created for DiBify.
 * Datetime: 31.10.2017 12:03
 * @author Timur Kasumov aka XAKEPEHOK
 */

namespace DiBify\DiBify\Mappers;


use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use DiBify\DiBify\Exceptions\SerializerException;

class DateTimeMapper implements MapperInterface
{

    private bool $immutable;

    private static self $instanceImmutable;
    private static self $instanceMutable;
    private static array $timezones = [];

    public function __construct($immutable = true)
    {
        $this->immutable = $immutable;
    }

    /**
     * Convert complex data (like object) to simple data (scalar, null, array)
     * @param DateTimeInterface $complex
     * @return int|null
     * @throws SerializerException
     */
    public function serialize($complex)
    {
        if (!is_a($complex, $this->classname())) {
            $type = gettype($complex);
            throw new SerializerException("'{$this->classname()}' expected, but '{$type}' type passed");
        }
        return (int) $complex->format('U');
    }

    /**
     * Convert simple data (scalar, null, array) into complex data (like object)
     * @param string|null $data
     * @return DateTimeInterface
     * @throws SerializerException
     */
    public function deserialize($data)
    {
        if (!is_string($data) && !is_integer($data)) {
            $type = gettype($data);
            throw new SerializerException("'{$this->classname()}' string expected, but '{$type}' type passed");
        }

        /** @var DateTime $classname */
        $classname = $this->classname();

        /** @var DateTime|DateTimeImmutable $datetime */
        $datetime = new $classname("@{$data}");
        if (!$datetime) {
            throw new SerializerException("Invalid format '{$data}' for restoring '{$this->classname()}'");
        }

        if (!isset(self::$timezones[date_default_timezone_get()])) {
            self::$timezones[date_default_timezone_get()] = new DateTimeZone(date_default_timezone_get());
        }

        return $datetime->setTimezone(self::$timezones[date_default_timezone_get()]);
    }

    /**
     * @return string
     */
    protected function classname(): string
    {
        return $this->immutable ? DateTimeImmutable::class : DateTime::class;
    }

    public static function getInstanceImmutable(): self
    {
        if (!isset(static::$instanceImmutable)) {
            static::$instanceImmutable = new static(true);
        }
        return static::$instanceImmutable;
    }

    public static function getInstanceMutable(): self
    {
        if (!isset(static::$instanceMutable)) {
            static::$instanceMutable = new static(false);
        }
        return static::$instanceMutable;
    }
}