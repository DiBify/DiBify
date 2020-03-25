<?php
/**
 * Created for DiBify
 * Date: 03.01.2020
 * @author Timur Kasumov (XAKEPEHOK)
 */

namespace DiBify\DiBify\Pool;


class IntPool implements PoolInterface
{

    /**
     * @var int
     */
    private $current;
    /**
     * @var int
     */
    private $pool;

    /**
     * IntPool constructor.
     * @param int $current
     * @param int|null $pool
     */
    public function __construct($current, $pool = null)
    {
        $this->current = (int) $current;
        $this->pool = (int) $pool;
    }

    public function getCurrent(): int
    {
        return $this->current;
    }

    public function getPool(): int
    {
        return $this->pool;
    }

    public function getResult(): int
    {
        return $this->current + $this->pool;
    }

    /**
     * @param int $value
     */
    public function add($value): void
    {
        $this->pool+= (int) $value;
    }

    /**
     * @param int $value
     */
    public function subtract($value): void
    {
        $this->pool-= (int) $value;
    }
}