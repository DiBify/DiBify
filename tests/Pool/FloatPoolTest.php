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
    private $pool;

    protected function setUp(): void
    {
        parent::setUp();
        $this->pool = new FloatPool(100.1, 10.1);
    }

    public function testGetCurrent()
    {
        $this->assertSame(100.1, $this->pool->getCurrent());
    }

    public function testGetPool()
    {
        $this->assertSame(10.1, $this->pool->getPool());
    }

    public function testGetResult()
    {
        $this->assertSame(110.2, $this->pool->getResult());
    }

    public function testAdd()
    {
        $this->pool->add(0.1);
        $this->assertSame(100.1, $this->pool->getCurrent());
        $this->assertSame(10.2, $this->pool->getPool());
        $this->assertSame(110.3, $this->pool->getResult());
    }

    public function testSubtract()
    {
        $this->pool->subtract(0.1);
        $this->assertSame(100.1, $this->pool->getCurrent());
        $this->assertSame(10.0, $this->pool->getPool());
        $this->assertSame(110.1, $this->pool->getResult());
    }
}
