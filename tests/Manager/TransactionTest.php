<?php
/**
 * Created for DiBify
 * Date: 15.03.2020
 * @author Timur Kasumov (XAKEPEHOK)
 */

namespace DiBify\DiBify\Manager;

use DiBify\DiBify\Id\Id;
use DiBify\DiBify\Mock\TestModel_1;
use DiBify\DiBify\Mock\TestModel_2;
use DiBify\DiBify\Mock\TestModel_3;
use DiBify\DiBify\Model\ModelInterface;
use Exception;
use PHPUnit\Framework\TestCase;
use TypeError;

class TransactionTest extends TestCase
{

    /** @var ModelInterface[] */
    private array $persisted;

    /** @var ModelInterface[] */
    private array $deleted;

    private Transaction $transaction;

    protected function setUp(): void
    {
        parent::setUp();

        $this->persisted = [
            new TestModel_1(),
            new TestModel_1(),
            new TestModel_2(),
        ];

        $this->deleted = [
            new TestModel_2(),
            new TestModel_2(),
            new TestModel_3(),
        ];

        Transaction::freeUpMemory();
        $this->transaction = new Transaction($this->persisted, $this->deleted);
    }

    public function invalidModelsProvider(): array
    {
        return [
            [
                [
                    1,
                    2,
                    3,
                ],
                [],
            ],
            [
                [],
                [
                    1,
                    2,
                    3,
                ],
            ],
            [
                [$this],
                [],
            ],
            [
                [],
                [$this],
            ],
        ];
    }

    /**
     * @dataProvider invalidModelsProvider
     * @param array $persisted
     * @param array $deleted
     * @throws Exception
     */
    public function testConstructWithNonModels(array $persisted, array $deleted): void
    {
        $this->expectException(TypeError::class);
        new Transaction($persisted, $deleted);
    }

    public function testId(): void
    {
        $this->assertInstanceOf(Id::class, $this->transaction->id());
        $this->assertNotEmpty((string)$this->transaction->id());
    }

    public function testGetPersisted(): void
    {
        $this->assertSame($this->persisted, $this->transaction->getPersisted());
        $this->assertCount(2, $this->transaction->getPersisted(TestModel_1::class));
        $this->assertCount(1, $this->transaction->getPersisted(TestModel_2::class));
    }

    public function testGetPersistedOne(): void
    {
        $this->assertSame(
            $this->persisted[0],
            $this->transaction->getPersistedOne(TestModel_1::class)
        );

        $this->assertNull(
            $this->transaction->getPersistedOne(TestModel_3::class)
        );
    }

    public function testIsPersisted(): void
    {
        $this->assertTrue($this->transaction->isPersisted($this->persisted[0]));
        $this->assertTrue($this->transaction->isPersisted($this->persisted[1]));
        $this->assertTrue($this->transaction->isPersisted($this->persisted[2]));
        $this->assertFalse($this->transaction->isPersisted($this->deleted[0]));
        $this->assertFalse($this->transaction->isPersisted($this->deleted[1]));
        $this->assertFalse($this->transaction->isPersisted($this->deleted[2]));
    }

    public function testResetPersisted(): void
    {
        $this->assertSame($this->persisted, $this->transaction->getPersisted());
        $this->transaction->resetPersisted();
        $this->assertEmpty($this->transaction->getPersisted());
    }

    public function testPersistsWith(): void
    {
        $model_1 = new TestModel_1();
        $model_2 = new TestModel_2();
        $model_3 = new TestModel_3();

        $this->assertFalse($this->transaction->isPersisted($model_1));
        $this->assertFalse($this->transaction->isPersisted($model_2));
        $this->assertFalse($this->transaction->isPersisted($model_3));

        Transaction::persistsWith($model_1, $model_2, $model_3);

        $this->assertFalse($this->transaction->isPersisted($model_1));
        $this->assertFalse($this->transaction->isPersisted($model_2));
        $this->assertFalse($this->transaction->isPersisted($model_3));

        $this->transaction->persists($model_3);
        $this->assertFalse($this->transaction->isPersisted($model_1));
        $this->assertFalse($this->transaction->isPersisted($model_2));
        $this->assertTrue($this->transaction->isPersisted($model_3));

        $this->transaction->persists($model_1);
        $this->assertTrue($this->transaction->isPersisted($model_1));
        $this->assertTrue($this->transaction->isPersisted($model_2));
        $this->assertTrue($this->transaction->isPersisted($model_3));

        $this->transaction->resetPersisted();
        $this->transaction->delete($model_1, $model_2, $model_3);
        $this->transaction->persists($model_1);
        $this->assertTrue($this->transaction->isPersisted($model_1));
        $this->assertFalse($this->transaction->isPersisted($model_2));
        $this->assertFalse($this->transaction->isPersisted($model_3));
    }

    public function testPersistsWithRecursion(): void
    {
        $model_1 = new TestModel_1();
        $model_2 = new TestModel_2();

        Transaction::persistsWith($model_1, $model_2);
        Transaction::persistsWith($model_2, $model_1);

        $this->transaction->persists($model_1);
        $this->assertTrue($this->transaction->isPersisted($model_1));
        $this->assertTrue($this->transaction->isPersisted($model_2));
    }

    public function testIsPersistsWith(): void
    {
        $primary = $this->deleted[0];
        $secondary = $this->deleted[1];
        $this->assertFalse(Transaction::isPersistsWith($primary, $secondary));

        Transaction::persistsWith($primary, $secondary);
        $this->assertTrue(Transaction::isPersistsWith($primary, $secondary));

        Transaction::freeUpMemory();
        $this->assertFalse(Transaction::isPersistsWith($primary, $secondary));
    }

    public function testWithPersistsDelete(): void
    {
        $model = $this->deleted[0];
        $shouldBeDeleted_1 = $this->persisted[0];
        $shouldBeDeleted_2 = $this->persisted[1];

        $this->assertTrue($this->transaction->isDeleted($model));
        $this->assertTrue($this->transaction->isPersisted($shouldBeDeleted_1));
        $this->assertTrue($this->transaction->isPersisted($shouldBeDeleted_2));

        Transaction::withPersistsDelete($model, $shouldBeDeleted_1, $shouldBeDeleted_2);

        $this->assertFalse($this->transaction->isPersisted($model));
        $this->assertTrue($this->transaction->isPersisted($shouldBeDeleted_1));
        $this->assertTrue($this->transaction->isPersisted($shouldBeDeleted_2));

        $this->transaction->persists($model);

        $this->assertTrue($this->transaction->isPersisted($model));
        $this->assertFalse($this->transaction->isPersisted($shouldBeDeleted_1));
        $this->assertFalse($this->transaction->isPersisted($shouldBeDeleted_2));
        $this->assertTrue($this->transaction->isDeleted($shouldBeDeleted_1));
        $this->assertTrue($this->transaction->isDeleted($shouldBeDeleted_2));

        $this->transaction->resetPersisted();
        $this->transaction->resetDeleted();
        $this->transaction->persists($shouldBeDeleted_1, $shouldBeDeleted_2);
        $this->transaction->persists($model);

        $this->assertTrue($this->transaction->isPersisted($model));
        $this->assertTrue($this->transaction->isPersisted($shouldBeDeleted_1));
        $this->assertTrue($this->transaction->isPersisted($shouldBeDeleted_2));
    }

    public function testWithPersistsDeleteRecursion(): void
    {
        $model_1 = new TestModel_1();
        $model_2 = new TestModel_2();

        Transaction::persistsWith($model_1, $model_2);
        Transaction::withPersistsDelete($model_2, $model_1);

        $this->transaction->persists($model_1);
        $this->assertTrue($this->transaction->isDeleted($model_1));
        $this->assertTrue($this->transaction->isPersisted($model_2));
    }

    public function testIsWithPersistsDelete(): void
    {
        $primary = $this->deleted[0];
        $secondary = $this->persisted[1];
        $this->assertFalse(Transaction::isWithPersistsDelete($primary, $secondary));

        Transaction::withPersistsDelete($primary, $secondary);
        $this->assertTrue(Transaction::isWithPersistsDelete($primary, $secondary));

        Transaction::freeUpMemory();
        $this->assertFalse(Transaction::isWithPersistsDelete($primary, $secondary));
    }

    public function testGetDeleted(): void
    {
        $this->assertSame($this->deleted, $this->transaction->getDeleted());
        $this->assertCount(2, $this->transaction->getDeleted(TestModel_2::class));
        $this->assertCount(1, $this->transaction->getDeleted(TestModel_3::class));
    }

    public function testGetDeletedOne(): void
    {
        $this->assertSame(
            $this->deleted[0],
            $this->transaction->getDeletedOne(TestModel_2::class)
        );

        $this->assertNull(
            $this->transaction->getDeletedOne(TestModel_1::class)
        );
    }

    public function testIsDeleted(): void
    {
        $this->assertTrue($this->transaction->isDeleted($this->deleted[0]));
        $this->assertTrue($this->transaction->isDeleted($this->deleted[1]));
        $this->assertTrue($this->transaction->isDeleted($this->deleted[2]));
        $this->assertFalse($this->transaction->isDeleted($this->persisted[0]));
        $this->assertFalse($this->transaction->isDeleted($this->persisted[1]));
        $this->assertFalse($this->transaction->isDeleted($this->persisted[2]));
    }

    public function testResetDeleted(): void
    {
        $this->assertSame($this->deleted, $this->transaction->getDeleted());
        $this->transaction->resetDeleted();
        $this->assertEmpty($this->transaction->getDeleted());
    }

    public function testDeleteWith(): void
    {
        $model_1 = $this->persisted[0];
        $model_2 = $this->persisted[1];
        $model_3 = $this->persisted[2];

        $this->assertFalse($this->transaction->isDeleted($model_1));
        $this->assertFalse($this->transaction->isDeleted($model_2));
        $this->assertFalse($this->transaction->isDeleted($model_3));

        Transaction::deleteWith($model_1, $model_2, $model_3);

        $this->assertFalse($this->transaction->isDeleted($model_1));
        $this->assertFalse($this->transaction->isDeleted($model_2));
        $this->assertFalse($this->transaction->isDeleted($model_3));

        $this->transaction->delete($model_3);
        $this->assertFalse($this->transaction->isDeleted($model_1));
        $this->assertFalse($this->transaction->isDeleted($model_2));
        $this->assertTrue($this->transaction->isDeleted($model_3));

        $this->transaction->delete($model_1);
        $this->assertTrue($this->transaction->isDeleted($model_1));
        $this->assertTrue($this->transaction->isDeleted($model_2));
        $this->assertTrue($this->transaction->isDeleted($model_3));

        $this->transaction->resetDeleted();
        $this->transaction->persists($model_1, $model_2, $model_3);

        $this->transaction->delete($model_1);
        $this->assertTrue($this->transaction->isDeleted($model_1));
        $this->assertFalse($this->transaction->isDeleted($model_2));
        $this->assertFalse($this->transaction->isDeleted($model_3));
    }

    public function testDeleteWithRecursion(): void
    {
        $model_1 = $this->persisted[0];
        $model_2 = $this->persisted[1];

        Transaction::deleteWith($model_1, $model_2);
        Transaction::deleteWith($model_2, $model_1);

        $this->transaction->delete($model_1);
        $this->assertTrue($this->transaction->isDeleted($model_1));
        $this->assertTrue($this->transaction->isDeleted($model_2));
    }

    public function testIsDeleteWith(): void
    {
        $primary = $this->persisted[0];
        $secondary = $this->persisted[1];
        $this->assertFalse(Transaction::isDeleteWith($primary, $secondary));

        Transaction::deleteWith($primary, $secondary);
        $this->assertTrue(Transaction::isDeleteWith($primary, $secondary));

        Transaction::freeUpMemory();
        $this->assertFalse(Transaction::isDeleteWith($primary, $secondary));
    }

    public function testWithDeletePersists(): void
    {
        $model = $this->persisted[0];
        $shouldBePersisted_1 = $this->deleted[0];
        $shouldBePersisted_2 = $this->deleted[1];

        $this->assertTrue($this->transaction->isPersisted($model));
        $this->assertTrue($this->transaction->isDeleted($shouldBePersisted_1));
        $this->assertTrue($this->transaction->isDeleted($shouldBePersisted_2));

        Transaction::withDeletePersists($model, $shouldBePersisted_1, $shouldBePersisted_2);

        $this->assertFalse($this->transaction->isDeleted($model));
        $this->assertTrue($this->transaction->isDeleted($shouldBePersisted_1));
        $this->assertTrue($this->transaction->isDeleted($shouldBePersisted_2));

        $this->transaction->delete($model);

        $this->assertTrue($this->transaction->isDeleted($model));
        $this->assertFalse($this->transaction->isDeleted($shouldBePersisted_1));
        $this->assertFalse($this->transaction->isDeleted($shouldBePersisted_2));
        $this->assertTrue($this->transaction->isPersisted($shouldBePersisted_1));
        $this->assertTrue($this->transaction->isPersisted($shouldBePersisted_2));

        $this->transaction->resetPersisted();
        $this->transaction->resetDeleted();
        $this->transaction->delete($shouldBePersisted_1, $shouldBePersisted_2);
        $this->transaction->delete($model);

        $this->assertTrue($this->transaction->isDeleted($model));
        $this->assertTrue($this->transaction->isDeleted($shouldBePersisted_1));
        $this->assertTrue($this->transaction->isDeleted($shouldBePersisted_2));
    }

    public function testWithDeletePersistsRecursion(): void
    {
        $model_1 = $this->persisted[0];
        $model_2 = $this->persisted[1];

        Transaction::deleteWith($model_1, $model_2);
        Transaction::withDeletePersists($model_2, $model_1);

        $this->transaction->delete($model_1);
        $this->assertTrue($this->transaction->isPersisted($model_1));
        $this->assertTrue($this->transaction->isDeleted($model_2));
    }

    public function testIsWithDeletePersists(): void
    {
        $primary = $this->persisted[0];
        $secondary = $this->deleted[1];
        $this->assertFalse(Transaction::isWithDeletePersists($primary, $secondary));

        Transaction::withDeletePersists($primary, $secondary);
        $this->assertTrue(Transaction::isWithDeletePersists($primary, $secondary));

        Transaction::freeUpMemory();
        $this->assertFalse(Transaction::isWithDeletePersists($primary, $secondary));
    }

    public function testFreeUpMemory(): void
    {
        $new = new TestModel_1();

        $persisted_1 = $this->persisted[0];
        $persisted_2 = $this->persisted[1];
        $persisted_3 = $this->persisted[2];

        $deleted_1 = $this->deleted[0];
        $deleted_2 = $this->deleted[1];
        $deleted_3 = $this->deleted[2];

        Transaction::persistsWith($deleted_1, $new);
        Transaction::withPersistsDelete($deleted_2, $persisted_2);
        Transaction::deleteWith($persisted_2, $persisted_3);
        Transaction::withDeletePersists($persisted_1, $deleted_3);

        Transaction::freeUpMemory();

        $this->transaction->persists($deleted_1);
        $this->assertTrue($this->transaction->isPersisted($deleted_1));
        $this->assertFalse($this->transaction->isPersisted($new));

        $this->transaction->persists($deleted_2);
        $this->assertTrue($this->transaction->isPersisted($deleted_2));
        $this->assertTrue($this->transaction->isPersisted($persisted_2));

        $this->transaction->delete($persisted_2);
        $this->assertTrue($this->transaction->isDeleted($persisted_2));
        $this->assertTrue($this->transaction->isPersisted($persisted_3));

        $this->transaction->delete($persisted_1);
        $this->assertTrue($this->transaction->isDeleted($persisted_1));
        $this->assertTrue($this->transaction->isDeleted($deleted_3));
    }

    public function testGetSetMetadata(): void
    {
        $this->assertNull($this->transaction->getMetadata('key'));
        $this->transaction->setMetadata('key', 'hello world');
        $this->assertSame('hello world', $this->transaction->getMetadata('key'));
    }

}
