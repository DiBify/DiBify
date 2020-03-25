<?php


namespace DiBify\DiBify\Repository\Components;


use DiBify\DiBify\Exceptions\InvalidArgumentException;

class Sorting
{

    const SORT_ASC = 'ASC';
    const SORT_DESC = 'DESC';
    /**
     * @var string
     */
    private $field;
    /**
     * @var string
     */
    private $direction;

    /**
     * Sort constructor.
     * @param string $field
     * @param string $direction
     * @throws InvalidArgumentException
     */
    public function __construct(string $field, string $direction = self::SORT_DESC)
    {
        $this->field = $field;

        if (!in_array($direction, [self::SORT_ASC, self::SORT_DESC])) {
            throw new InvalidArgumentException('Sort direction should be ASC or DESC only');
        }

        $this->direction = $direction;
    }

    /**
     * @return string
     */
    public function getField(): string
    {
        return $this->field;
    }

    /**
     * @return string
     */
    public function getDirection(): string
    {
        return $this->direction;
    }

}