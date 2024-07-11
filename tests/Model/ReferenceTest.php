<?php
/**
 * Created for DiBify.
 * Datetime: 02.08.2018 15:20
 * @author Timur Kasumov aka XAKEPEHOK
 */

namespace DiBify\DiBify\Model;

use DiBify\DiBify\Exceptions\ReferenceDataException;
use DiBify\DiBify\Id\Id;
use DiBify\DiBify\Mock\TestModel_1;
use PHPUnit\Framework\TestCase;

class ReferenceTest extends TestCase
{

    /** @var Reference */
    private $reference;

    /** @var ModelInterface */
    private $model;

    protected function setUp(): void
    {
        parent::setUp();
        Reference::freeUpMemory();
        $this->model = new TestModel_1(1);
        $this->reference = Reference::to($this->model);
    }

    public function testCreateFromNameAndId(): void
    {
        $id = new Id(1);
        $pointer = Reference::create(TestModel_1::getModelAlias(), $id);
        $this->assertSame((string) $id, (string) $pointer->id());
    }

    public function testCreateFromClassAndId(): void
    {
        $reference = Reference::create(TestModel_1::class, new Id(1));
        $this->assertEquals(TestModel_1::getModelAlias(), $reference->getModelAlias());
    }

    public function testCreateScalarId(): void
    {
        $reference = Reference::create(TestModel_1::class, 1);
        $this->assertEquals(TestModel_1::getModelAlias(), $reference->getModelAlias());
    }

    public function testToNewModel(): void
    {
        $model = new TestModel_1();
        $reference = Reference::to($model);

        $this->assertSame($model, $reference->getModel());
        $this->assertSame($model->id(), $reference->getModel()->id());

    }

    public function testId(): void
    {
        $this->assertSame((string) $this->model->id(), (string) $this->reference->id());
    }

    public function testGetModelAlias(): void
    {
        $this->assertEquals($this->model::getModelAlias(), $this->reference->getModelAlias());
    }

    public function testGetModel(): void
    {
        $this->assertSame($this->model, $this->reference->getModel());
    }

    public function testToJson(): void
    {
        $expected = json_encode([
            'alias' => $this->model::getModelAlias(),
            'id' => (string) $this->model->id(),
        ]);
        $this->assertEquals($expected, json_encode($this->reference));
    }

    public function testFromArray(): void
    {
        $reference = Reference::fromArray([
            'alias' => TestModel_1::getModelAlias(),
            'id' => (string) $this->model->id()
        ]);

        $this->assertEquals($this->reference->id(), $reference->id());
        $this->assertEquals($this->reference->getModelAlias(), $reference->getModelAlias());

        $this->assertNull(Reference::fromArray(null));
    }

    /**
     * @dataProvider invalidReferenceDataProvider
     * @param array $array
     */
    public function testFromInvalidArray(array $array): void
    {
        $this->expectException(ReferenceDataException::class);
        $this->expectExceptionCode(1);
        Reference::fromArray($array);
    }

    public function testFromJson(): void
    {
        $json = json_encode($this->reference);
        $reference = Reference::fromJson($json);
        $this->assertEquals($this->reference->id(), $reference->id());
        $this->assertEquals($this->reference->getModelAlias(), $reference->getModelAlias());

        $this->assertNull(Reference::fromJson('null'));
    }

    /**
     * @dataProvider invalidReferenceDataProvider
     * @param array $array
     */
    public function testFromInvalidJson(array $array): void
    {
        $this->expectException(ReferenceDataException::class);
        $this->expectExceptionCode(1);
        Reference::fromJson(json_encode($array));
    }

    public static function invalidReferenceDataProvider(): array
    {
        return [
            [[]],
            [['alias' => 'name']],
            [['id' => '1']],
        ];
    }

}
