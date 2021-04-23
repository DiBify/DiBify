<?php

namespace DiBify\DiBify\Locker;


use DiBify\DiBify\Mock\TestModel_1;
use DiBify\DiBify\Model\Reference;
use DiBify\DiBify\Model\ModelInterface;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class DummyLockerTest extends TestCase
{

    private DummyLocker $dummy;
    protected ModelInterface $locker_1;
    private ModelInterface $locker_2;
    private ModelInterface $locker_3;
    private ModelInterface $model_1;
    private ModelInterface $model_2;
    private ModelInterface $model_3;

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

    public function testLock(): void
    {
        $locks = [];
        $this->dummy->addOnLockHandler(function (ModelInterface $model, ModelInterface $locker) use (&$locks) {
            $locks[] = [$model, $locker];
        });

        $this->dummy->addOnUnlockHandler(function () {
            throw new RuntimeException();
        });

        $this->assertTrue($this->dummy->lock($this->model_1, $this->locker_1));
        $this->assertTrue($this->dummy->lock($this->model_3, $this->locker_3));
        $this->assertFalse($this->dummy->lock($this->model_3, $this->locker_1));

        $this->assertSame([
            [$this->model_1, $this->locker_1],
            [$this->model_3, $this->locker_3],
        ], $locks);
    }

    public function testUnlock(): void
    {
        $unlocks = [];
        $this->dummy->addOnLockHandler(function () {
            throw new RuntimeException();
        });

        $this->dummy->addOnUnlockHandler(function (ModelInterface $model) use (&$unlocks) {
            $unlocks[] = $model;
        });

        $this->assertTrue($this->dummy->unlock($this->model_1, $this->locker_1));
        $this->assertTrue($this->dummy->unlock($this->model_3, $this->locker_3));
        $this->assertFalse($this->dummy->unlock($this->model_2, $this->locker_1));

        $this->assertSame([$this->model_1, $this->model_3], $unlocks);
    }

    public function testPassLock(): void
    {
        $this->dummy->addOnLockHandler(function (ModelInterface $model, ModelInterface $locker) {
            $this->assertSame($this->model_1,$model);
            $this->assertSame($this->locker_2,$locker);
        });

        $this->dummy->addOnUnlockHandler(function () {
            throw new RuntimeException();
        });

        $this->assertFalse($this->dummy->passLock($this->model_2, $this->locker_1, $this->locker_2));
        $this->assertFalse($this->dummy->passLock($this->model_2, $this->locker_1, $this->locker_3));
        $this->assertTrue($this->dummy->passLock($this->model_1, $this->locker_1, $this->locker_2));
    }

    public function testIsLockedFor(): void
    {
        $this->assertTrue($this->dummy->isLockedFor($this->model_1, $this->locker_2));
        $this->assertFalse($this->dummy->isLockedFor($this->model_1, $this->locker_1));
        $this->assertFalse($this->dummy->isLockedFor($this->model_3, $this->locker_1));
    }

    public function testIsLockedForWithTimeout(): void
    {
        $this->dummy->addOnLockHandler(function (ModelInterface $model, ModelInterface $locker) {
            $this->assertSame($this->model_1, $model);
            $this->assertSame($this->locker_1, $locker);
        });

        $this->dummy->addOnUnlockHandler(function (ModelInterface $model){
            $this->assertSame($this->model_1, $model);
        });

        $this->dummy->lock($this->model_1, $this->locker_1, 3);
        $this->assertTrue($this->dummy->isLockedFor($this->model_1, $this->locker_2));
        sleep(1);
        $this->assertTrue($this->dummy->isLockedFor($this->model_1, $this->locker_2));
        sleep(1);
        $this->assertTrue($this->dummy->isLockedFor($this->model_1, $this->locker_2));
        sleep(2);
        $this->assertFalse($this->dummy->isLockedFor($this->model_1, $this->locker_2));
    }

    public function testGetLocker(): void
    {
        $lockerReference = $this->dummy->getLocker($this->model_1);
        $this->assertTrue($lockerReference->isFor($this->locker_1));

        $modelReference = Reference::to($this->model_1);
        $lockerReference = $this->dummy->getLocker($modelReference);
        $this->assertTrue($lockerReference->isFor($this->locker_1));

        $lockerReference = $this->dummy->getLocker($this->model_3);
        $this->assertNull($lockerReference);
    }

    public function testGetLockerWithTimeout(): void
    {
        $this->dummy->addOnLockHandler(function (ModelInterface $model, ModelInterface $locker) {
            $this->assertSame($this->model_1, $model);
            $this->assertSame($this->locker_1, $locker);
        });

        $this->dummy->addOnUnlockHandler(function (ModelInterface $model){
            $this->assertSame($this->model_1, $model);
        });

        $this->dummy->lock($this->model_1, $this->locker_1, 3);
        $lockerReference = $this->dummy->getLocker($this->model_1);
        $this->assertTrue($lockerReference->isFor($this->locker_1));
        sleep(1);
        $lockerReference = $this->dummy->getLocker($this->model_1);
        $this->assertTrue($lockerReference->isFor($this->locker_1));
        sleep(1);
        $lockerReference = $this->dummy->getLocker($this->model_1);
        $this->assertTrue($lockerReference->isFor($this->locker_1));
        sleep(2);
        $this->assertNull($this->dummy->getLocker($this->model_1));
    }

    public function testGetDefaultTimeout(): void
    {
        $this->assertEquals(60, $this->dummy->getDefaultTimeout());
    }
}
