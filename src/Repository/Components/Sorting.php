<?php
/**
 * Created for DiBify
 * Date: 08.03.2020
 * @author Timur Kasumov (XAKEPEHOK)
 */

namespace DiBify\DiBify\Repository\Components;


use DiBify\DiBify\Exceptions\InvalidArgumentException;

class Sorting
{

    const SORT_ASC = 'ASC';
    const SORT_DESC = 'DESC';

    private string $field;

    private string $direction;

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

    public function getField(): string
    {
        return $this->field;
    }

    public function getDirection(): string
    {
        return $this->direction;
    }

}