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

class CommitTest extends TestCase
{

    /** @var ModelInterface[] */
    private $persisted;

    /** @var ModelInterface[] */
    private $deleted;

    /** @var Commit */
    private $commit;

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

        $this->commit = new Commit($this->persisted, $this->deleted);
    }

    public function invalidModelsProvider(): array
    {
        return [
            [[1, 2, 3], []],
            [[], [1, 2, 3]],
            [[$this], []],
            [[], [$this]],
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
        new Commit($persisted, $deleted);
    }

    public function testId()
    {
        $this->assertInstanceOf(Id::class, $this->commit->id());
        $this->assertNotEmpty((string) $this->commit->id());
    }

    public function testGetPersisted()
    {
        $this->assertSame($this->persisted, $this->commit->getPersisted());
        $this->assertCount(2, $this->commit->getPersisted(TestModel_1::class));
        $this->assertCount(1, $this->commit->getPersisted(TestModel_2::class));
    }

    public function testGetDeleted()
    {
        $this->assertSame($this->deleted, $this->commit->getDeleted());
        $this->assertCount(2, $this->commit->getDeleted(TestModel_2::class));
        $this->assertCount(1, $this->commit->getDeleted(TestModel_3::class));
    }
}
