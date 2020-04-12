<?php
/**
 * Created for DiBify.
 * Datetime: 31.10.2017 12:03
 * @author Timur Kasumov aka XAKEPEHOK
 */

namespace DiBify\DiBify\Mappers;


use DateTime;
use DateTimeInterface;
use DiBify\DiBify\Exceptions\SerializerException;

class DateTimeMapper implements MapperInterface
{
    /**
     * @var string
     */
    private $format;

    public function __construct($format = 'Y-m-d H:i:s')
    {
        $this->format = $format;
    }

    /**
     * Convert complex data (like object) to simpe data (scalar, null, array)
     * @param DateTimeInterface $complex
     * @return string|null
     * @throws SerializerException
     */
    public function serialize($complex)
    {
        if (!is_a($complex, $this->classname())) {
            $type = gettype($complex);
            throw new SerializerException("'{$this->classname()}' expected, but '{$type}' type passed");
        }
        return $complex->format($this->format);
    }

    /**
     * Convert simple data (scalar, null, array) into complex data (like object)
     * @param string|null $data
     * @return DateTimeInterface
     * @throws SerializerException
     */
    public function deserialize($data)
    {
        if (!is_string($data)) {
            $type = gettype($data);
            throw new SerializerException("'{$this->classname()}' string expected, but '{$type}' type passed");
        }

        /** @var DateTime $classname */
        $classname = $this->classname();
        $datetime = $classname::createFromFormat($this->format, $data);
        if (!$datetime) {
            throw new SerializerException("Invalid format '{$data}' for restoring '{$this->classname()}'");
        }

        return $datetime;
    }

    /**
     * @return string
     */
    protected function classname(): string
    {
        return DateTime::class;
    }
}