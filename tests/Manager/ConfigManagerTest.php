<?php

namespace DiBify\DiBify\Manager;

use DiBify\DiBify\Exceptions\InvalidArgumentException;
use DiBify\DiBify\Exceptions\UnknownModelException;
use DiBify\DiBify\Id\Id;
use DiBify\DiBify\Id\IdGeneratorInterface;
use DiBify\DiBify\Id\UuidGenerator;
use DiBify\DiBify\Mock\TestModel_1;
use DiBify\DiBify\Mock\TestModel_1_1;
use DiBify\DiBify\Mock\TestModel_2;
use DiBify\DiBify\Mock\TestModel_2_2;
use DiBify\DiBify\Mock\TestModel_3;
use DiBify\DiBify\Mock\TestRepo_1;
use DiBify\DiBify\Mock\TestRepo_2;
use DiBify\DiBify\Model\Reference;
use PHPUnit\Framework\TestCase;

class ConfigManagerTest extends TestCase
{

    /** @var ConfigManager */
    private $manager;

    /** @var IdGeneratorInterface */
    private $idGenerator_1;

    /** @var IdGeneratorInterface */
    private $idGenerator_2;

    /** @var TestRepo_1 */
    private $repo_1;

    /** @var TestRepo_2 */
    private $repo_2;

    /** @var array */
    private $callableCount;

    protected function setUp(): void
    {
        parent::setUp();
        $this->idGenerator_1 = new UuidGenerator();
        $this->idGenerator_2 = new UuidGenerator();

        $this->repo_1 = new TestRepo_1();
        $this->repo_2 = new TestRepo_2();

        $this->callableCount = [];

        $this->manager = new ConfigManager();

        $this->manager->add(
            $this->repo_1,
            [TestModel_1::class, TestModel_1_1::class],
            $this->idGenerator_1
        );

        $this->manager->add(
            function (string $class) {
                $this->callableCount[$class] = ($this->callableCount[$class] ?? 0) + 1;
                return $this->repo_2;
            },
            [TestModel_2::class, TestModel_2_2::class],
            $this->idGenerator_2
        );
    }

    public function getRepoDataProvider(): array
    {
        return [
            [TestModel_1::class, TestRepo_1::class, TestModel_1::class, 0],
            [TestModel_1::getModelAlias(), TestRepo_1::class, TestModel_1::class, 0],
            [new TestModel_1(), TestRepo_1::class, TestModel_1::class, 0],
            [Reference::create(TestModel_1::getModelAlias(), new Id(1)), TestRepo_1::class, TestModel_1::class, 0],
            [TestModel_1_1::class, TestRepo_1::class, TestModel_1_1::class, 0],

            [TestModel_2::class, TestRepo_2::class, TestModel_2::class, 1],
            [TestModel_2::getModelAlias(), TestRepo_2::class, TestModel_2::class, 1],
            [new TestModel_2(), TestRepo_2::class, TestModel_2::class, 1],
            [Reference::create(TestModel_2::getModelAlias(), new Id(2)), TestRepo_2::class, TestModel_2::class, 1],
            [TestModel_2_2::class, TestRepo_2::class, TestModel_2_2::class, 1],
        ];
    }

    /**
     * @param $argument
     * @param string $repository
     * @param string $modelClass
     * @param int $callableCount
     * @throws InvalidArgumentException
     * @throws UnknownModelException
     * @dataProvider getRepoDataProvider
     */
    public function testGetRepository($argument, string $repository, string $modelClass, int $callableCount)
    {
        $this->assertInstanceOf(
            $repository,
            $this->manager->getRepository($argument)
        );

        if ($callableCount > 0) {
            $this->assertArrayHasKey($modelClass, $this->callableCount);
            $this->assertSame($callableCount, $this->callableCount[$modelClass]);
        }
    }

    public function testGetModelClasses(): void
    {
        $this->assertEquals([
            TestModel_1::class,
            TestModel_1_1::class,
            TestModel_2::class,
            TestModel_2_2::class,
        ], $this->manager->getModelClasses());
    }

    public function testGetRepositoryCallableCallsCount()
    {
        $this->assertSame(
            $this->repo_2,
            $this->manager->getRepository(TestModel_2::class)
        );

        $this->assertSame(
            $this->repo_2,
            $this->manager->getRepository(TestModel_2_2::class)
        );

        $this->assertSame(1, $this->callableCount[TestModel_2::class]);
    }

    public function testGetRepositoryUnknownModel()
    {
        $this->expectException(UnknownModelException::class);
        $this->manager->getRepository(TestModel_3::class);
    }

    public function getIdGeneratorDataProvider(): array
    {
        return [
            [TestModel_1::class, 1],
            [TestModel_1::getModelAlias(), 1],
            [new TestModel_1(), 1],
            [Reference::create(TestModel_1::getModelAlias(), new Id(1)), 1],
            [TestModel_1_1::class, 1],

            [TestModel_2::class, 2],
            [TestModel_2::getModelAlias(), 2],
            [new TestModel_2(), 2],
            [Reference::create(TestModel_2::getModelAlias(), new Id(1)), 2],
            [TestModel_2_2::class, 2],
        ];
    }

    /**
     * @param $argument
     * @param $generatorNumber
     * @throws InvalidArgumentException
     * @throws UnknownModelException
     * @dataProvider getIdGeneratorDataProvider
     */
    public function testGetIdGenerator($argument, $generatorNumber)
    {
        $this->assertSame(
            $this->{'idGenerator_' . $generatorNumber},
            $this->manager->getIdGenerator($argument)
        );
    }

    public function testGetIdGeneratorUnknownModel()
    {
        $this->expectException(UnknownModelException::class);
        $this->manager->getIdGenerator(TestModel_3::class);
    }
}
