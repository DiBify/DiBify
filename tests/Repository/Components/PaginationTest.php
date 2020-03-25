<?php
/**
 * Created for DiBify
 * Date: 15.03.2020
 * @author Timur Kasumov (XAKEPEHOK)
 */

namespace DiBify\DiBify\Repository\Components;

use PHPUnit\Framework\TestCase;

class PaginationTest extends TestCase
{

    /** @var Pagination */
    private $pagination;

    protected function setUp(): void
    {
        parent::setUp();
        $this->pagination = new Pagination(10, 100);
    }

    public function testGetNumber()
    {
        $this->assertSame(10, $this->pagination->getNumber());
    }

    public function testSetNumber()
    {
        $this->pagination->setNumber(11);
        $this->assertSame(11, $this->pagination->getNumber());
    }

    public function testGetSize()
    {
        $this->assertSame(100, $this->pagination->getSize());
    }

    public function testSetSize()
    {
        $this->pagination->setSize(101);
        $this->assertSame(101, $this->pagination->getSize());
    }
}
