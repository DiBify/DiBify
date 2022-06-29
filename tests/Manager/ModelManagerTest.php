<?php
/**
 * @author Timur Kasumov (aka XAKEPEHOK)
 * Datetime: 24.03.2017 20:37
 */

namespace DiBify\DiBify\Manager;


use DiBify\DiBify\Exceptions\LockedModelException;
use DiBify\DiBify\Id\Id;
use DiBify\DiBify\Id\UuidGenerator;
use DiBify\DiBify\Locker\DummyLocker;
use DiBify\DiBify\Locker\Lock\Lock;
use DiBify\DiBify\Locker\LockerInterface;
use DiBify\DiBify\Locker\Lock\ServiceLock;
use DiBify\DiBify\Mock\TestModel_1;
use DiBify\DiBify\Mock\TestModel_2;
use DiBify\DiBify\Mock\TestRepo_1;
use DiBify\DiBify\Mock\TestRepo_2;
use DiBify\DiBify\Model\Reference;
use PHPUnit\Framework\TestCase;
use Throwable;

class ModelManagerTest extends TestCase
{

    /** @var LockerInterface */
    private $locker;

    /** @var TestRepo_1 */
    private $repo_1;

    /** @var TestRepo_2 */
    private $repo_2;

    /** @var array */
    private $onEvents = [];

    /** @var ModelManager */
    private $manager;


    protected function setUp(): void
    {
        parent::setUp();
        $this->locker = new DummyLocker();
        $idGenerator = new UuidGenerator();

        $this->repo_1 = new TestRepo_1();
        $this->repo_2 = new TestRepo_2();

        $configManager = new ConfigManager();

        $configManager->add(
            $this->repo_1,
            [TestModel_1::class],
            $idGenerator
        );

        $configManager->add(
            $this->repo_2,
            [TestModel_2::class],
            $idGenerator
        );

        $this->onEvents = [];

        $this->manager = new ModelManager(
            $configManager,
            $this->locker,
            function (Transaction $commit) {$this->onEvents['before'] = $commit;},
            function (Transaction $commit) {$this->onEvents['after'] = $commit;},
            function (Transaction $commit) {$this->onEvents['exception'] = $commit;}
        );
    }

    public function testGetLocker(): void
    {
        $this->assertSame($this->locker, $this->manager->getLocker());
    }

    public function testGetRepository(): void
    {
        $this->assertSame(
            $this->repo_1,
            $this->manager->getRepository(TestModel_1::class)
        );

        $this->assertSame(
            $this->repo_2,
            $this->manager->getRepository(TestModel_2::class)
        );
    }

    public function testFindByReference(): void
    {
        $reference = Reference::create(TestModel_1::getModelAlias(), 2);
        $model = $this->manager->findByReference($reference);
        $this->assertTrue($reference->isFor($model));
    }

    public function testFindByReferences(): void
    {
        $reference_1 = Reference::create(TestModel_1::getModelAlias(), 1);
        $reference_2 = Reference::create(TestModel_2::getModelAlias(), 2);

        $storage = $this->manager->findByReferences($reference_1, $reference_2);
        $this->assertTrue($reference_1->isFor($storage[$reference_1]));
        $this->assertTrue($reference_2->isFor($storage[$reference_2]));
    }

    public function testFindByAnyTypeId(): void
    {
        $model = new TestModel_1(10);
        $this->assertSame(
            $model,
            $this->manager->findByAnyTypeId($model)
        );

        $reference = Reference::create(TestModel_1::getModelAlias(), 2);
        $model = $this->manager->findByAnyTypeId($reference);
        $this->assertInstanceOf(TestModel_1::class, $model);
        $this->assertEquals('2', (string) $model->id());

        $id = new Id(1);
        $model = $this->manager->findByAnyTypeId($id, TestModel_1::class);
        $this->assertInstanceOf(TestModel_1::class, $model);
        $this->assertEquals('1', (string) $model->id());

        $id = new Id(3);
        $model = $this->manager->findByAnyTypeId($id, TestModel_2::class);
        $this->assertInstanceOf(TestModel_2::class, $model);
        $this->assertEquals('3', (string) $model->id());
    }

    //todo public function testRefreshOne(): void

    //todo public function testRefreshMany(): void

    public function testCommitWithServiceUnlock(): void
    {
        $lockedBy = new TestModel_1(1);
        $lock = new ServiceLock($lockedBy, 3);

        $model_1 = new TestModel_1();
        $model_2 = new TestModel_2();
        $model_3 = $this->repo_1->findById(3);

        $transaction = new Transaction();
        $transaction->persists($model_1, $model_2);
        $transaction->delete($model_3);

        $this->manager->commit($transaction, $lock);

        $this->assertInstanceOf(Transaction::class, $transaction);
        $this->assertContains($model_1, $transaction->getPersisted(TestModel_1::class));
        $this->assertContains($model_2, $transaction->getPersisted(TestModel_2::class));
        $this->assertContains($model_3, $transaction->getDeleted(TestModel_1::class));

        $this->assertTrue($model_1->id()->isAssigned());
        $this->assertTrue($model_2->id()->isAssigned());

        $this->assertSame($model_1, $this->repo_1->findById($model_1->id()));
        $this->assertSame($model_2, $this->repo_2->findById($model_2->id()));
        $this->assertNull($this->repo_1->findById(3));

        $locker = $this->manager->getLocker();
        $this->assertNull($locker->getLock($model_1));
        $this->assertNull($locker->getLock($model_2));
        $this->assertNull($locker->getLock($model_3));

        $this->assertSame($transaction, $this->onEvents['before'] ?? null);
        $this->assertSame($transaction, $this->onEvents['after'] ?? null);
        $this->assertArrayNotHasKey('exception', $this->onEvents);
    }

    public function testCommitWithServiceLockException(): void
    {
        $lockedBy = new TestModel_1(1);
        $lock = new ServiceLock($lockedBy, 3);

        $model = new TestModel_1('exception');
        try {
            $this->manager->commit(new Transaction([$model]), $lock);
        } catch (Throwable $throwable) {
            $this->assertInstanceOf(Throwable::class, $throwable);
        }

        $locker = $this->manager->getLocker();
        $this->assertNull($locker->getLock($model));

        $this->assertInstanceOf(Transaction::class, $this->onEvents['before'] ?? null);
        $this->assertInstanceOf(Transaction::class, $this->onEvents['exception'] ?? null);
        $this->assertArrayNotHasKey('after', $this->onEvents);
    }

    public function testCommitWithLock(): void
    {
        $lockedBy = new TestModel_1(1);
        $lock = new Lock($lockedBy, 2);

        $model = new TestModel_1();
        $this->manager->commit(new Transaction([$model]), $lock);

        $locker = $this->manager->getLocker();
        $this->assertTrue($locker->getLock($model)->isCompatible($lock));
    }

    public function testCommitWithLockException(): void
    {
        $lockedBy = new TestModel_1(1);
        $lock = new Lock($lockedBy, 2);

        $model = new TestModel_1('exception');

        try {
            $this->manager->commit(new Transaction([$model]), $lock);
        } catch (Throwable $throwable) {
            $this->assertInstanceOf(Throwable::class, $throwable);
        }

        $locker = $this->manager->getLocker();
        $this->assertTrue($locker->getLock($model)->isCompatible($lock));
    }

    public function testCommitWithLockedModelException(): void
    {
        $this->expectException(LockedModelException::class);

        $lockedBy = new TestModel_1(1);
        $lock = new Lock($lockedBy, 2);
        $model = new TestModel_2(2);

        $this->manager->getLocker()->lock($model, new Lock(new TestModel_1(11)));

        $this->manager->commit(new Transaction([$model]), $lock);
    }

    public function testCommitWithLockedModelExceptionButWithoutLock(): void
    {
        $this->expectException(LockedModelException::class);
        $model = new TestModel_2(2);
        $this->manager->getLocker()->lock($model, new Lock(new TestModel_1(11)));
        $this->manager->commit(new Transaction([$model]));
    }

    public function testCommitModelEvents(): void
    {
        $model = new TestModel_1();

        $this->assertFalse($model->onBeforeCommit);
        $this->assertFalse($model->onAfterCommit);

        $transaction = new Transaction();
        $transaction->persists($model);

        $this->assertFalse($model->onBeforeCommit);
        $this->assertFalse($model->onAfterCommit);

        $this->manager->commit($transaction);

        $this->assertTrue($model->onBeforeCommit);
        $this->assertTrue($model->onAfterCommit);
    }

    public function testFreeUpMemory(): void
    {
        $repo = $this->manager->getRepository(TestModel_1::class);
        $repo->findById(1);
        $this->assertCount(1, $this->repo_1->getRegistered());
        $this->manager->freeUpMemory();
        $this->assertCount(0, $this->repo_1->getRegistered());
    }

}