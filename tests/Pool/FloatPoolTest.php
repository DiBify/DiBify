<?php
/**
 * Created for DiBify
 * Date: 03.01.2020
 * @author Timur Kasumov (XAKEPEHOK)
 */

namespace DiBify\DiBify\Pool;

use PHPUnit\Framework\TestCase;

class FloatPoolTest extends TestCase
{

    /** @var FloatPool */
    private PoolInterface $pool;

    protected function setUp(): void
    {
        parent::setUp();
        $this->pool = new FloatPool(100.10, 10.10);
    }

    public function testGetCurrent(): void
    {
        $this->assertSame(100.1, $this->pool->getCurrent());
    }

    public function testGetPool(): void
    {
        $this->assertSame(10.1, $this->pool->getPool());
    }

    public function testGetResult(): void
    {
        $this->assertSame(110.2, round($this->pool->getResult(), 1));
    }

    public function testAdd(): void
    {
        $this->pool->add(0.1);
        $this->assertSame(100.1, $this->pool->getCurrent());
        $this->assertSame(10.2, $this->pool->getPool());
        $this->assertSame(110.3, $this->pool->getResult());
    }

    public function testSubtract(): void
    {
        $this->pool->subtract(0.1);
        $this->assertSame(100.1, $this->pool->getCurrent());
        $this->assertSame(10.0, $this->pool->getPool());
        $this->assertSame(110.1, $this->pool->getResult());
    }

    public function testMerge(): void
    {
        $this->pool->merge();
        $this->assertSame(110.2, round($this->pool->getCurrent(), 2));
        $this->assertSame(0.0, $this->pool->getPool());
        $this->assertSame(110.2, round($this->pool->getResult(), 2));
    }
}
