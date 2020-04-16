<?php
/**
 * Created for DiBify.
 * Datetime: 02.08.2018 15:20
 * @author Timur Kasumov aka XAKEPEHOK
 */

namespace DiBify\DiBify\Model;

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

    public function testCreateFromNameAndId()
    {
        $id = new Id(1);
        $pointer = Reference::create(TestModel_1::getModelAlias(), $id);
        $this->assertSame((string) $id, (string) $pointer->id());
    }

    public function testCreateFromClassAndId()
    {
        $reference = Reference::create(TestModel_1::class, new Id(1));
        $this->assertEquals(TestModel_1::getModelAlias(), $reference->getModelAlias());
    }

    public function testCreateScalarId()
    {
        $reference = Reference::create(TestModel_1::class, 1);
        $this->assertEquals(TestModel_1::getModelAlias(), $reference->getModelAlias());
    }

    public function testToNewModel()
    {
        $model = new TestModel_1();
        $reference = Reference::to($model);

        $this->assertSame($model, $reference->getModel());
        $this->assertSame($model->id(), $reference->getModel()->id());

    }

    public function testId()
    {
        $this->assertSame((string) $this->model->id(), (string) $this->reference->id());
    }

    public function testGetModelAlias()
    {
        $this->assertEquals($this->model::getModelAlias(), $this->reference->getModelAlias());
    }

    public function testGetModel()
    {
        $this->assertSame($this->model, $this->reference->getModel());
    }

    public function testToJson()
    {
        $expected = json_encode([
            'alias' => $this->model::getModelAlias(),
            'id' => (string) $this->model->id(),
        ]);
        $this->assertEquals($expected, json_encode($this->reference));
    }

    public function testFromJson()
    {
        $json = json_encode($this->reference);
        $reference = Reference::fromJson($json);
        $this->assertEquals($this->reference->id(), $reference->id());
        $this->assertEquals($this->reference->getModelAlias(), $reference->getModelAlias());
    }

    public function testFromJsonNullOrFail()
    {
        $this->assertNull(Reference::fromJson(''));
        $this->assertNull(Reference::fromJson('null'));
        $this->assertNull(Reference::fromJson('[]'));
        $this->assertNull(Reference::fromJson('{}'));
        $this->assertNull(Reference::fromJson('{"alias": "name"}'));
        $this->assertNull(Reference::fromJson('{"id": 123}'));
    }

}
