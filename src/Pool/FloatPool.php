<?php
/**
 * Created for DiBify
 * Date: 03.01.2020
 * @author Timur Kasumov (XAKEPEHOK)
 */

namespace DiBify\DiBify\Pool;


class FloatPool implements PoolInterface
{

    private float $current;

    private float $pool;

    /**
     * FloatPool constructor.
     * @param float $current
     * @param float|null $pool
     */
    public function __construct($current, $pool = null)
    {
        $this->current = (float) $current;
        $this->pool = (float) $pool;
    }

    public function getCurrent(): float
    {
        return $this->current;
    }

    public function getPool(): ?float
    {
        return $this->pool;
    }

    public function getResult(): float
    {
        return $this->current + $this->pool;
    }

    /**
     * @param float $value
     */
    public function add($value): void
    {
        $this->pool+= (float) $value;
    }

    /**
     * @param float $value
     */
    public function subtract($value): void
    {
        $this->pool-= (float) $value;
    }

    public function merge(): void
    {
        $this->current = $this->getResult();
        $this->pool = 0;
    }
}