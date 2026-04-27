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

    private ?self $next;

    /**
     * Sort constructor.
     * @param string $field
     * @param string $direction
     * @param self|null $next
     * @throws InvalidArgumentException
     */
    public function __construct(string $field, string $direction = self::SORT_DESC, ?self $next = null)
    {
        $this->field = $field;

        if (!in_array($direction, [self::SORT_ASC, self::SORT_DESC])) {
            throw new InvalidArgumentException('Sort direction should be ASC or DESC only');
        }

        $this->direction = $direction;
        $this->next = $next;
    }

    public function getField(): string
    {
        return $this->field;
    }

    public function getDirection(): string
    {
        return $this->direction;
    }

    public function getNext(): ?self
    {
        return $this->next;
    }

    /**
     * @return self[]
     */
    public function getSortingArray(): array
    {
        $result = [];
        $current = $this;
        while ($current !== null) {
            $result[] = $current;
            $current = $current->next;
        }
        return $result;
    }

}