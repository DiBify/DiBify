<?php
/**
 * Created for DiBify
 * Date: 15.03.2020
 * @author Timur Kasumov (XAKEPEHOK)
 */

namespace DiBify\DiBify\Manager;

use DiBify\DiBify\Exceptions\NotModelInterfaceException;
use DiBify\DiBify\Id\Id;
use DiBify\DiBify\Mock\TestModel_1;
use DiBify\DiBify\Mock\TestModel_2;
use DiBify\DiBify\Mock\TestModel_3;
use DiBify\DiBify\Model\ModelInterface;
use PHPUnit\Framework\TestCase;

class TransactionTest extends TestCase
{

    /** @var ModelInterface[] */
    private $persisted;

    /** @var ModelInterface[] */
    private $deleted;

    /** @var Transaction */
    private $transaction;

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
     * @throws NotModelInterfaceException
     */
    public function testConstructWithNonModels(array $persisted, array $deleted)
    {
        $this->expectException(NotModelInterfaceException::class);
        new Transaction($persisted, $deleted);
    }

    public function testId()
    {
        $this->assertInstanceOf(Id::class, $this->transaction->id());
        $this->assertNotEmpty((string)$this->transaction->id());
    }

    public function testGetPersisted()
    {
        $this->assertSame($this->persisted, $this->transaction->getPersisted());
        $this->assertCount(2, $this->transaction->getPersisted(TestModel_1::class));
        $this->assertCount(1, $this->transaction->getPersisted(TestModel_2::class));
    }

    public function testGetPersistedOne()
    {
        $this->assertSame(
            $this->persisted[0],
            $this->transaction->getPersistedOne(TestModel_1::class)
        );

        $this->assertNull(
            $this->transaction->getPersistedOne(TestModel_3::class)
        );
    }

    public function testIsPersisted()
    {
        $this->assertTrue($this->transaction->isPersisted($this->persisted[0]));
        $this->assertTrue($this->transaction->isPersisted($this->persisted[1]));
        $this->assertTrue($this->transaction->isPersisted($this->persisted[2]));
        $this->assertFalse($this->transaction->isPersisted($this->deleted[0]));
        $this->assertFalse($this->transaction->isPersisted($this->deleted[1]));
        $this->assertFalse($this->transaction->isPersisted($this->deleted[2]));
    }

    public function testResetPersisted()
    {
        $this->assertSame($this->persisted, $this->transaction->getPersisted());
        $this->transaction->resetPersisted();
        $this->assertEmpty($this->transaction->getPersisted());
    }

    public function testGetDeleted()
    {
        $this->assertSame($this->deleted, $this->transaction->getDeleted());
        $this->assertCount(2, $this->transaction->getDeleted(TestModel_2::class));
        $this->assertCount(1, $this->transaction->getDeleted(TestModel_3::class));
    }

    public function testGetDeletedOne()
    {
        $this->assertSame(
            $this->deleted[0],
            $this->transaction->getDeletedOne(TestModel_2::class)
        );

        $this->assertNull(
            $this->transaction->getDeletedOne(TestModel_1::class)
        );
    }

    public function testIsDeleted()
    {
        $this->assertTrue($this->transaction->isDeleted($this->deleted[0]));
        $this->assertTrue($this->transaction->isDeleted($this->deleted[1]));
        $this->assertTrue($this->transaction->isDeleted($this->deleted[2]));
        $this->assertFalse($this->transaction->isDeleted($this->persisted[0]));
        $this->assertFalse($this->transaction->isDeleted($this->persisted[1]));
        $this->assertFalse($this->transaction->isDeleted($this->persisted[2]));
    }

    public function testResetDeleted()
    {
        $this->assertSame($this->deleted, $this->transaction->getDeleted());
        $this->transaction->resetDeleted();
        $this->assertEmpty($this->transaction->getDeleted());
    }

}
