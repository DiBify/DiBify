<?php

namespace DiBify\DiBify\Locker;


use DiBify\DiBify\Locker\Lock\Lock;
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

    private Lock $lock_1;
    private Lock $lock_2;
    private Lock $lock_22;
    private Lock $lock_3;

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

        $this->lock_1 = new Lock($this->locker_1);
        $this->lock_2 = new Lock($this->locker_2);
        $this->lock_22 = new Lock($this->locker_2, 'identity');
        $this->lock_3 = new Lock($this->locker_3, 'identity');

        $this->dummy->lock($this->model_1, $this->lock_1);
        $this->dummy->lock($this->model_2, $this->lock_2);
    }

    public function testLock(): void
    {
        $locks = [];
        $this->dummy->addOnLockHandler(function (ModelInterface $model, Lock $lock) use (&$locks) {
            $locks[json_encode([Reference::to($model), $lock])] = [$model, $lock];
        });

        $this->dummy->addOnUnlockHandler(function () {
            throw new RuntimeException();
        });

        $this->assertTrue($this->dummy->lock($this->model_1, $this->lock_1));
        $this->assertTrue($this->dummy->lock($this->model_2, $this->lock_2));
        $this->assertTrue($this->dummy->lock($this->model_3, $this->lock_3));

        //Test second lock
        $this->assertTrue($this->dummy->lock($this->model_1, $this->lock_1));
        $this->assertTrue($this->dummy->lock($this->model_2, $this->lock_2));
        $this->assertTrue($this->dummy->lock($this->model_3, $this->lock_3));

        $this->assertFalse($this->dummy->lock($this->model_1, $this->lock_2));
        $this->assertFalse($this->dummy->lock($this->model_2, $this->lock_3));
        $this->assertFalse($this->dummy->lock($this->model_3, $this->lock_2));
        $this->assertFalse($this->dummy->lock($this->model_3, new Lock($this->model_3)));

        $this->assertSame([
            [$this->model_1, $this->lock_1],
            [$this->model_2, $this->lock_2],
            [$this->model_3, $this->lock_3],
        ], array_values($locks));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionCode(100);
        $this->dummy->lock($this->model_1, $this->lock_2, new RuntimeException('100', 100));
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

        $this->assertTrue($this->dummy->unlock($this->model_1, $this->lock_1));
        $this->assertTrue($this->dummy->unlock($this->model_3, $this->lock_3));
        $this->assertFalse($this->dummy->unlock($this->model_2, $this->lock_1));
        $this->assertFalse($this->dummy->unlock($this->model_2, $this->lock_22));

        $this->assertSame([$this->model_1, $this->model_3], $unlocks);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionCode(200);
        $this->dummy->unlock($this->model_2, $this->lock_22,  new RuntimeException('200', 200));
    }

    public function testPassLock(): void
    {
        $this->dummy->addOnLockHandler(function (ModelInterface $model, Lock $lock) {
            $this->assertSame($this->model_1, $model);
            $this->assertEquals($this->lock_2, $lock);
        });

        $this->dummy->addOnUnlockHandler(function () {
            throw new RuntimeException();
        });

        $this->assertFalse($this->dummy->passLock($this->model_2, $this->lock_1, $this->lock_2));
        $this->assertFalse($this->dummy->passLock($this->model_2, $this->lock_1, $this->lock_3));
        $this->assertTrue($this->dummy->passLock($this->model_1, $this->lock_1, $this->lock_2));
        $this->assertFalse($this->dummy->passLock($this->model_1, $this->lock_22, $this->lock_3));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionCode(300);
        $this->dummy->passLock($this->model_1, $this->lock_22, $this->lock_3, new RuntimeException('300', 300));
    }

    public function testWaitForLock(): void
    {
        $this->assertTrue($this->dummy->lock($this->model_1, $this->lock_1));
        $this->assertTrue($this->dummy->lock($this->model_2, $this->lock_2));
        $this->assertTrue($this->dummy->lock($this->model_3, $this->lock_2));

        $this->assertFalse(
            $this->dummy->waitForLock(
                [$this->model_1, $this->model_2, $this->model_3],
                5,
                $this->lock_3,
            )
        );

        $this->dummy->lock($this->model_1, $this->lock_1->setTimeout(2));
        $this->dummy->lock($this->model_2, $this->lock_2->setTimeout(2));
        $this->dummy->lock($this->model_3, $this->lock_2->setTimeout(2));

        $this->assertTrue(
            $this->dummy->waitForLock(
                [$this->model_1, $this->model_2, $this->model_3],
                5,
                $this->lock_3,
            )
        );

        $this->expectException(RuntimeException::class);
        $this->expectExceptionCode(400);
        $this->dummy->waitForLock(
            [$this->model_1, $this->model_2, $this->model_3],
            5,
            $this->lock_2,
            new RuntimeException('400', 400)
        );
    }

    public function testIsLockedFor(): void
    {
        $this->assertTrue($this->dummy->isLockedFor($this->model_1, $this->lock_2));
        $this->assertTrue($this->dummy->isLockedFor($this->model_2, $this->lock_1));
        $this->assertFalse($this->dummy->isLockedFor($this->model_2, $this->lock_2));
        $this->assertTrue($this->dummy->isLockedFor($this->model_2, $this->lock_22));
        $this->assertFalse($this->dummy->isLockedFor($this->model_1, $this->lock_1));
        $this->assertFalse($this->dummy->isLockedFor($this->model_3, $this->lock_1));
    }

    public function testIsLockedForWithTimeout(): void
    {
        $this->dummy->addOnLockHandler(function (ModelInterface $model, Lock $lock) {
            $this->assertSame($this->model_1, $model);
            $this->assertTrue($this->lock_1->isCompatible($lock));
        });

        $this->dummy->addOnUnlockHandler(function (ModelInterface $model){
            $this->assertSame($this->model_1, $model);
        });

        $this->dummy->lock($this->model_1, $this->lock_1->setTimeout(3));
        $this->assertTrue($this->dummy->isLockedFor($this->model_1, $this->lock_2));
        sleep(1);
        $this->assertTrue($this->dummy->isLockedFor($this->model_1, $this->lock_2));
        sleep(1);
        $this->assertTrue($this->dummy->isLockedFor($this->model_1, $this->lock_2));
        sleep(2);
        $this->assertFalse($this->dummy->isLockedFor($this->model_1, $this->lock_2));
    }

    public function testGetLock(): void
    {
        $this->assertTrue($this->dummy->getLock($this->model_1)->isCompatible($this->lock_1));

        $newLock = $this->lock_3->setTimeout(2);
        $this->dummy->lock($this->model_3, $newLock);
        $this->assertTrue($this->dummy->getLock($this->model_3)->isCompatible($newLock));
        sleep(3);
        $this->assertNull($this->dummy->getLock($this->model_3));
    }

    public function testGetDefaultTimeout(): void
    {
        $this->assertEquals(60, $this->dummy->getDefaultTimeout());
    }
}
