<?php
/**
 * Created for DiBify
 * Date: 03.01.2020
 * @author Timur Kasumov (XAKEPEHOK)
 */

namespace DiBify\DiBify\Pool;

use PHPUnit\Framework\TestCase;

class IntPoolTest extends TestCase
{

    /** @var IntPool */
    private $pool;

    protected function setUp(): void
    {
        parent::setUp();
        $this->pool = new IntPool(100, 10);
    }

    public function testGetCurrent()
    {
        $this->assertSame(100, $this->pool->getCurrent());
    }

    public function testGetPool()
    {
        $this->assertSame(10, $this->pool->getPool());
    }

    public function testGetResult()
    {
        $this->assertSame(110, $this->pool->getResult());
    }

    public function testAdd()
    {
        $this->pool->add(1);
        $this->assertSame(100, $this->pool->getCurrent());
        $this->assertSame(11, $this->pool->getPool());
        $this->assertSame(111, $this->pool->getResult());
    }

    public function testSubtract()
    {
        $this->pool->subtract(1);
        $this->assertSame(100, $this->pool->getCurrent());
        $this->assertSame(9, $this->pool->getPool());
        $this->assertSame(109, $this->pool->getResult());
    }
}