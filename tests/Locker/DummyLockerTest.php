<?php

namespace DiBify\DiBify\Locker;


use DiBify\DiBify\Mock\TestModel_1;
use DiBify\DiBify\Model\Link;
use DiBify\DiBify\Model\ModelInterface;
use PHPUnit\Framework\TestCase;

class DummyLockerTest extends TestCase
{

    /** @var DummyLocker */
    private $dummy;

    /** @var ModelInterface */
    private $locker_1;

    /** @var ModelInterface */
    private $locker_2;

    /** @var ModelInterface */
    private $locker_3;

    /** @var ModelInterface */
    private $model_1;

    /** @var ModelInterface */
    private $model_2;

    /** @var ModelInterface */
    private $model_3;

    protected function setUp(): void
    {
        parent::setUp();
        $this->dummy = new DummyLocker();

        $this->locker_1 = new TestModel_1(1);
        $this->model_1 = new TestModel_1(2);

        $this->locker_2 = new TestModel_1(11);
        $this->model_2 = new TestModel_1(22);

        $this->locker_3 = new TestModel_1(111);
        $this->model_3 = new TestModel_1(222);

        $this->dummy->lock($this->model_1, $this->locker_1);
        $this->dummy->lock($this->model_2, $this->locker_2);
    }

    public function testLock()
    {
        $this->assertTrue($this->dummy->lock($this->model_1, $this->locker_1));
        $this->assertTrue($this->dummy->lock($this->model_3, $this->locker_3));
        $this->assertFalse($this->dummy->lock($this->model_3, $this->locker_1));
    }

    public function testUnlock()
    {
        $this->assertTrue($this->dummy->unlock($this->model_1, $this->locker_1));
        $this->assertTrue($this->dummy->unlock($this->model_3, $this->locker_3));
        $this->assertFalse($this->dummy->unlock($this->model_2, $this->locker_1));
    }

    public function testPassLock()
    {
        $this->assertFalse($this->dummy->passLock($this->model_2, $this->locker_1, $this->locker_2));
        $this->assertFalse($this->dummy->passLock($this->model_2, $this->locker_1, $this->locker_3));
        $this->assertTrue($this->dummy->passLock($this->model_1, $this->locker_1, $this->locker_2));
    }

    public function testIsLockedFor()
    {
        $this->assertTrue($this->dummy->isLockedFor($this->model_1, $this->locker_2));
        $this->assertFalse($this->dummy->isLockedFor($this->model_1, $this->locker_1));
        $this->assertFalse($this->dummy->isLockedFor($this->model_3, $this->locker_1));
    }

    public function testGetLocker()
    {
        $lockerLink = $this->dummy->getLocker($this->model_1);
        $this->assertTrue($lockerLink->isFor($this->locker_1));

        $modelLink = Link::to($this->model_1);
        $lockerLink = $this->dummy->getLocker($modelLink);
        $this->assertTrue($lockerLink->isFor($this->locker_1));

        $lockerLink = $this->dummy->getLocker($this->model_3);
        $this->assertNull($lockerLink);
    }

    public function testGetDefaultTimeout()
    {
        $this->assertEquals(10, $this->dummy->getDefaultTimeout());
    }
}
