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

    public function testGetNext()
    {
        $next = new Sorting('createdAt', Sorting::SORT_DESC);
        $sorting = new Sorting('name', Sorting::SORT_ASC, $next);
        $this->assertSame($next, $sorting->getNext());
    }

    public function testGetNextNull()
    {
        $this->assertNull($this->sortAsc->getNext());
    }

    public function testGetSortingArray()
    {
        $third = new Sorting('id', Sorting::SORT_ASC);
        $second = new Sorting('createdAt', Sorting::SORT_DESC, $third);
        $first = new Sorting('name', Sorting::SORT_ASC, $second);

        $array = $first->getSortingArray();

        $this->assertCount(3, $array);
        $this->assertSame($first, $array[0]);
        $this->assertSame($second, $array[1]);
        $this->assertSame($third, $array[2]);
    }

    public function testGetSortingArraySingle()
    {
        $array = $this->sortAsc->getSortingArray();
        $this->assertCount(1, $array);
        $this->assertSame($this->sortAsc, $array[0]);
    }

}
