<?php


namespace DiBify\DiBify\Repository\Components;


class Pagination
{
    /**
     * @var int
     */
    protected $number;
    /**
     * @var int
     */
    protected $size;

    /**
     * Pagination constructor.
     * @param int $number
     * @param int $size
     */
    public function __construct(int $number, int $size)
    {
        $this->number = $number;
        $this->size = $size;
    }
    /**
     * @return int
     */
    public function getNumber(): int
    {
        return $this->number;
    }
    /**
     * @param int $number
     * @return self
     */
    public function setNumber(int $number): self
    {
        $this->number = $number;
        return $this;
    }
    /**
     * @return int
     */
    public function getSize(): int
    {
        return $this->size;
    }
    /**
     * @param int $size
     * @return self
     */
    public function setSize(int $size): self
    {
        $this->size = $size;
        return $this;
    }
}