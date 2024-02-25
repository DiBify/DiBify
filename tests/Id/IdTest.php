<?php
/**
 * @author Timur Kasumov (aka XAKEPEHOK)
 * Datetime: 20.06.2017 19:46
 */

namespace DiBify\DiBify\Id;


use DiBify\DiBify\Exceptions\UnassignedIdException;
use DiBify\DiBify\Mock\TestModel_1;
use DiBify\DiBify\Model\Reference;
use PHPUnit\Framework\TestCase;

class IdTest extends TestCase
{

    private Id $permanent;

    private Id $temp;

    protected function setUp(): void
    {
        $this->permanent = new Id(1);
        $this->temp = new Id();
    }

    public function testConstructWithoutPermanent(): void
    {
        $this->assertFalse($this->temp->isAssigned());
    }

    public function testConstructWithPermanent(): void
    {
        $this->assertTrue($this->permanent->isAssigned());
    }

    public function testSetAlreadyAssignedPermanentId()
    {
        $this->assertFalse($this->permanent->assign(1));
    }

    public function testGet()
    {
        $this->assertSame('1', $this->permanent->get());
    }

    public function testAssign()
    {
        $this->assertTrue($this->temp->assign(1));
    }

    public function testAssignEmpty()
    {
        $this->assertFalse($this->temp->assign(''));
    }

    public function testIsEqual()
    {
        $modelPermanent = new TestModel_1();
        $modelPermanent->id = $this->permanent;

        $modelTemp = new TestModel_1();
        $modelTemp->id = $this->temp;

        $referencePermanent = Reference::to($modelPermanent);
        $referenceTemp = Reference::to($modelTemp);

        $id = new Id(1);

        $this->assertTrue($this->permanent->isEqual($id));
        $this->assertTrue($this->permanent->isEqual(1));
        $this->assertTrue($this->permanent->isEqual($modelPermanent));
        $this->assertTrue($modelTemp->id()->isEqual($modelTemp));
        $this->assertTrue($this->permanent->isEqual($referencePermanent));
        $this->assertTrue($this->temp->isEqual($referenceTemp));
        $this->assertFalse((new Id())->isEqual($modelTemp));
        $this->assertFalse($this->permanent->isEqual($this->temp));
        $this->assertFalse($this->permanent->isEqual(2));
        $this->assertFalse($this->permanent->isEqual($modelTemp));
        $this->assertFalse($this->permanent->isEqual($referenceTemp));
        $this->assertFalse($this->temp->isEqual($referencePermanent));
    }

    public function testAssertIsAssigned(): void
    {
        $id_assigned = new Id('1');
        $id_assigned->assertIsAssigned();

        $id_empty = new Id();
        $this->expectException(UnassignedIdException::class);
        $id_empty->assertIsAssigned();
    }
    
    public function testToString()
    {
        $this->assertEquals(1, (string) $this->permanent);
        $this->assertEquals((string) $this->temp, '');
    }

    public function testJsonSerialize()
    {
        $this->assertEquals(
            '"1"',
            json_encode(new Id(1))
        );

        $this->assertEquals(
            '"hello"',
            json_encode(new Id('hello'))
        );
    }

    public function testNonStrictCompare()
    {
        $id1 = new Id(10);
        $id2 = new Id();
        $id2->assign(10);
        $this->assertTrue($id1 == $id2);

        $id1 = new Id();
        $id2 = new Id();
        $this->assertTrue($id1 == $id2);

        $id1 = new Id(1);
        $id2 = new Id();
        $this->assertFalse($id1 == $id2);

        $id1 = new Id();
        $id2 = new Id();
        $id2->assign(2);
        $this->assertFalse($id1 == $id2);

        $id1 = new Id();
        $id2 = null;
        $this->assertFalse($id1 == $id2);
    }

}