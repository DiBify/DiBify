<?php
/**
 * @author Timur Kasumov (aka XAKEPEHOK)
 * Datetime: 24.03.2017 20:37
 */

namespace DiBify\DiBify\Manager;


use DiBify\DiBify\Exceptions\InvalidArgumentException;
use DiBify\DiBify\Exceptions\LockedModelException;
use DiBify\DiBify\Exceptions\NotModelInterfaceException;
use DiBify\DiBify\Id\Id;
use DiBify\DiBify\Id\IdGeneratorInterface;
use DiBify\DiBify\Id\UuidGenerator;
use DiBify\DiBify\Locker\DummyLocker;
use DiBify\DiBify\Locker\Lock\Lock;
use DiBify\DiBify\Locker\LockerInterface;
use DiBify\DiBify\Locker\Lock\ServiceLock;
use DiBify\DiBify\Mock\TestModel_1;
use DiBify\DiBify\Mock\TestModel_2;
use DiBify\DiBify\Mock\TestRepo_1;
use DiBify\DiBify\Mock\TestRepo_2;
use DiBify\DiBify\Model\Link;
use PHPUnit\Framework\TestCase;
use Throwable;

class ModelManagerTest extends TestCase
{

    /** @var LockerInterface */
    private $locker;

    /** @var IdGeneratorInterface */
    private $idGenerator;

    /** @var TestRepo_1 */
    private $repo_1;

    /** @var TestRepo_2 */
    private $repo_2;

    /** @var ConfigManager */
    private $configManager;

    /** @var array */
    private $onEvents = [];

    /** @var ModelManager */
    private $manager;


    protected function setUp(): void
    {
        parent::setUp();
        $this->locker = new DummyLocker();
        $this->idGenerator = new UuidGenerator();

        $this->repo_1 = new TestRepo_1();
        $this->repo_2 = new TestRepo_2();

        $this->configManager = new ConfigManager();

        $this->configManager->add(
            $this->repo_1,
            [TestModel_1::class],
            $this->idGenerator
        );

        $this->configManager->add(
            $this->repo_2,
            [TestModel_2::class],
            $this->idGenerator
        );

        $this->onEvents = [];

        $this->manager = new ModelManager(
            $this->configManager,
            $this->locker,
            function (Commit $commit) {$this->onEvents['before'] = $commit;},
            function (Commit $commit) {$this->onEvents['after'] = $commit;},
            function (Commit $commit) {$this->onEvents['exception'] = $commit;}
        );
    }

    public function testGetLocker()
    {
        $this->assertSame($this->locker, $this->manager->getLocker());
    }

    public function testGetRepository()
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

    public function testFindByLink()
    {
        $link = new Link(TestModel_1::getModelAlias(), 2);
        $model = $this->manager->findByLink($link);
        $this->assertTrue($link->isFor($model));
    }

    public function testFindByLinks()
    {
        $link_1 = new Link(TestModel_1::getModelAlias(), 1);
        $link_2 = new Link(TestModel_2::getModelAlias(), 2);

        $storage = $this->manager->findByLinks([$link_1, $link_2]);
        $this->assertTrue($link_1->isFor($storage[$link_1]));
        $this->assertTrue($link_2->isFor($storage[$link_2]));
    }

    public function testFindByLinksWithNotLink()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionCode(1);
        $this->manager->findByLinks([$this]);
    }

    public function testFindByAnyTypeId()
    {
        $model = new TestModel_1(10);
        $this->assertSame(
            $model,
            $this->manager->findByAnyTypeId($model)
        );

        $link = new Link(TestModel_1::getModelAlias(), 2);
        $model = $this->manager->findByAnyTypeId($link);
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

    public function testPersists()
    {
        $model_1 = new TestModel_1(1);
        $model_2 = new TestModel_2(2);

        $this->assertFalse($this->manager->isPersisted($model_1));
        $this->assertFalse($this->manager->isPersisted($model_2));

        $this->manager->persists($model_1);
        $this->manager->persists([$model_2]);

        $this->assertTrue($this->manager->isPersisted($model_1));
        $this->assertTrue($this->manager->isPersisted($model_2));

        $this->manager->resetPersisted();
        $this->assertFalse($this->manager->isPersisted($model_1));
        $this->assertFalse($this->manager->isPersisted($model_2));
    }

    public function testPersistsNotModelException()
    {
        $this->expectException(NotModelInterfaceException::class);
        $this->manager->persists($this);
    }

    public function testDelete()
    {
        $model_1 = new TestModel_1(1);
        $model_2 = new TestModel_2(2);

        $this->assertFalse($this->manager->isDeleted($model_1));
        $this->assertFalse($this->manager->isDeleted($model_2));

        $this->manager->delete($model_1);
        $this->manager->delete([$model_2]);

        $this->assertTrue($this->manager->isDeleted($model_1));
        $this->assertTrue($this->manager->isDeleted($model_2));

        $this->manager->resetDeleted();
        $this->assertFalse($this->manager->isDeleted($model_1));
        $this->assertFalse($this->manager->isDeleted($model_2));
    }

    public function testDeleteNotModelException()
    {
        $this->expectException(NotModelInterfaceException::class);
        $this->manager->delete($this);
    }

    public function testCommitWithServiceUnlock()
    {
        $lockedBy = new TestModel_1(1);
        $lock = new ServiceLock($lockedBy, 3);

        $model_1 = new TestModel_1();
        $model_2 = new TestModel_2();
        $model_3 = $this->repo_1->findById(3);

        $this->manager->persists([$model_1, $model_2]);
        $this->manager->delete([$model_3]);
        $commit = $this->manager->commit($lock);

        $this->assertInstanceOf(Commit::class, $commit);
        $this->assertContains($model_1, $commit->getPersisted(TestModel_1::class));
        $this->assertContains($model_2, $commit->getPersisted(TestModel_2::class));
        $this->assertContains($model_3, $commit->getDeleted(TestModel_1::class));

        $this->assertTrue($model_1->id()->isAssigned());
        $this->assertTrue($model_2->id()->isAssigned());

        $this->assertSame($model_1, $this->repo_1->findById($model_1->id()));
        $this->assertSame($model_2, $this->repo_2->findById($model_2->id()));
        $this->assertNull($this->repo_1->findById(3));

        $locker = $this->manager->getLocker();
        $this->assertNull($locker->getLocker($model_1));
        $this->assertNull($locker->getLocker($model_2));
        $this->assertNull($locker->getLocker($model_3));

        $this->assertSame($commit, $this->onEvents['before'] ?? null);
        $this->assertSame($commit, $this->onEvents['after'] ?? null);
        $this->assertArrayNotHasKey('exception', $this->onEvents);
    }

    public function testCommitWithServiceLockException()
    {
        $lockedBy = new TestModel_1(1);
        $lock = new ServiceLock($lockedBy, 3);

        $model = new TestModel_1('exception');

        try {
            $this->manager->persists($model);
            $this->manager->commit($lock);
        } catch (Throwable $throwable) {
            $this->assertInstanceOf(Throwable::class, $throwable);
        }

        $locker = $this->manager->getLocker();
        $this->assertNull($locker->getLocker($model));

        $this->assertInstanceOf(Commit::class, $this->onEvents['before'] ?? null);
        $this->assertInstanceOf(Commit::class, $this->onEvents['exception'] ?? null);
        $this->assertArrayNotHasKey('after', $this->onEvents);
    }

    public function testCommitWithLock()
    {
        $lockedBy = new TestModel_1(1);
        $lock = new Lock($lockedBy, 2);

        $model = new TestModel_1();

        $this->manager->persists([$model]);
        $this->manager->commit($lock);

        $locker = $this->manager->getLocker();
        $this->assertTrue($locker->getLocker($model)->isFor($lockedBy));
    }

    public function testCommitWithLockException()
    {
        $lockedBy = new TestModel_1(1);
        $lock = new Lock($lockedBy, 2);

        $model = new TestModel_1('exception');

        try {
            $this->manager->persists($model);
            $this->manager->commit($lock);
        } catch (Throwable $throwable) {
            $this->assertInstanceOf(Throwable::class, $throwable);
        }

        $locker = $this->manager->getLocker();
        $this->assertTrue($locker->getLocker($model)->isFor($lockedBy));
    }

    public function testCommitWithLockedModelException()
    {
        $this->expectException(LockedModelException::class);

        $lockedBy = new TestModel_1(1);
        $lock = new Lock($lockedBy, 2);

        $model = new TestModel_2(2);
        $this->manager->persists($model);

        $this->manager->getLocker()->lock($model, new TestModel_1(11));

        $this->manager->commit($lock);
    }

    public function testFreeUpMemory()
    {
        $repo = $this->manager->getRepository(TestModel_1::class);
        $repo->findById(1);
        $this->assertCount(1, $this->repo_1->getRegistered());
        $this->manager->freeUpMemory();
        $this->assertCount(0, $this->repo_1->getRegistered());
    }

}