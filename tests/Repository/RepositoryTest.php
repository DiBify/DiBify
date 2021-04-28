<?php
/**
 * Created for dibify
 * Date: 06.04.2021
 * @author Timur Kasumov (XAKEPEHOK)
 */

namespace DiBify\DiBify\Repository;

use DiBify\DiBify\Id\Id;
use DiBify\DiBify\Mappers\IdMapper;
use DiBify\DiBify\Mappers\MapperInterface;
use DiBify\DiBify\Mappers\ModelMapper;
use DiBify\DiBify\Mappers\NullOrMapper;
use DiBify\DiBify\Mappers\StringMapper;
use DiBify\DiBify\Mock\TestModel_1;
use DiBify\DiBify\Mock\TestStorage;
use DiBify\DiBify\Replicator\DirectReplicator;
use DiBify\DiBify\Repository\Storage\StorageData;
use PHPUnit\Framework\TestCase;

class RepositoryTest extends TestCase
{

    private Repository $repo;

    private TestStorage $storage;

    protected function setUp(): void
    {
        parent::setUp();

        $this->storage = new TestStorage([
            new StorageData('1', ['otherId' => null, 'custom' => null]),
            new StorageData('2', ['otherId' => 20, 'custom' => 'hello']),
            new StorageData('3', ['otherId' => 30, 'custom' => 'world']),
        ]);

        $this->repo = new class(new DirectReplicator($this->storage)) extends Repository {

            public function classes(): array
            {
                return [TestModel_1::class];
            }

            protected function getMapper(): MapperInterface
            {
                $class = current($this->classes());
                return new ModelMapper($class, [
                    'id' => IdMapper::getInstance(),
                    'otherId' => new NullOrMapper(IdMapper::getInstance()),
                    'custom' => new NullOrMapper(StringMapper::getInstance()),
                ]);
            }
        };
    }

    public function testRefresh(): void
    {
        $model_1 = $this->repo->findById(1);
        $this->assertInstanceOf(TestModel_1::class, $model_1);
        $this->assertEquals(null, $model_1->getOtherId());
        $this->assertSame(null, $model_1->getCustom());

        $model_2 = $this->repo->findById(2);
        $this->assertInstanceOf(TestModel_1::class, $model_2);
        $this->assertEquals(new Id(20), $model_2->getOtherId());
        $this->assertSame('hello', $model_2->getCustom());

        $model_3 = $this->repo->findById(3);
        $this->assertInstanceOf(TestModel_1::class, $model_3);
        $this->assertEquals(new Id(30), $model_3->getOtherId());
        $this->assertSame('world', $model_3->getCustom());

        $this->storage->data[1] = new StorageData('1', ['otherId' => '100', 'custom' => 'example']);
        unset($this->storage->data[3]);

        $this->assertSame($model_1, $this->repo->findById(1));
        $this->assertNull($this->repo->findById(3));

        $changes = $this->repo->refresh($model_1, $model_2, $model_3);

        $this->assertNotNull($changes[$model_1]);
        $this->assertNotNull($changes[$model_2]);
        $this->assertNotSame($model_1, $changes[$model_1]);
        $this->assertSame($model_2, $changes[$model_2]);
        $this->assertNull($changes[$model_3]);
    }
}
