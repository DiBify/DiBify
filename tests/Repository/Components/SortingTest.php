<?php
/**
 * Created for DiBify
 * Date: 15.03.2020
 * @author Timur Kasumov (XAKEPEHOK)
 */

namespace DiBify\DiBify\Repository\Components;

use DiBify\DiBify\Exceptions\InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class SortingTest extends TestCase
{

    /** @var Sorting */
    private $sortAsc;

    /** @var Sorting */
    private $sortDesc;

    protected function setUp(): void
    {
        parent::setUp();
        $this->sortAsc = new Sorting('name', Sorting::SORT_ASC);
        $this->sortDesc = new Sorting('name', Sorting::SORT_DESC);
    }

    public function testGetField()
    {
        $this->assertSame('name', $this->sortAsc->getField());
    }

    public function testGetDirection()
    {
        $this->assertSame(Sorting::SORT_ASC, $this->sortAsc->getDirection());
        $this->assertSame(Sorting::SORT_DESC, $this->sortDesc->getDirection());
    }

    public function testInvalidDirection()
    {
        $this->expectException(InvalidArgumentException::class);
        new Sorting('name', 'qwerty');
    }

}
