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

class LinkTest extends TestCase
{

    /** @var Link */
    private $link;

    /** @var ModelInterface */
    private $model;

    protected function setUp(): void
    {
        parent::setUp();
        Link::freeUpMemory();
        $this->model = new TestModel_1(1);
        $this->link = Link::to($this->model);
    }

    public function testCreateFromNameAndId()
    {
        $id = new Id(1);
        $pointer = Link::create(TestModel_1::getModelAlias(), $id);
        $this->assertSame((string) $id, (string) $pointer->id());
    }

    public function testCreateFromClassAndId()
    {
        $link = Link::create(TestModel_1::class, new Id(1));
        $this->assertEquals(TestModel_1::getModelAlias(), $link->getModelAlias());
    }

    public function testCreateScalarId()
    {
        $link = Link::create(TestModel_1::class, 1);
        $this->assertEquals(TestModel_1::getModelAlias(), $link->getModelAlias());
    }

    public function testToNewModel()
    {
        $model = new TestModel_1();
        $link = Link::to($model);

        $this->assertSame($model, $link->getModel());
        $this->assertSame($model->id(), $link->getModel()->id());

    }

    public function testId()
    {
        $this->assertSame((string) $this->model->id(), (string) $this->link->id());
    }

    public function testGetModelAlias()
    {
        $this->assertEquals($this->model::getModelAlias(), $this->link->getModelAlias());
    }

    public function testGetModel()
    {
        $this->assertSame($this->model, $this->link->getModel());
    }

    public function testToJson()
    {
        $expected = json_encode([
            'alias' => $this->model::getModelAlias(),
            'id' => (string) $this->model->id(),
        ]);
        $this->assertEquals($expected, json_encode($this->link));
    }

    public function testFromJson()
    {
        $json = json_encode($this->link);
        $link = Link::fromJson($json);
        $this->assertEquals($this->link->id(), $link->id());
        $this->assertEquals($this->link->getModelAlias(), $link->getModelAlias());
    }

    public function testFromJsonNullOrFail()
    {
        $this->assertNull(Link::fromJson(''));
        $this->assertNull(Link::fromJson('null'));
        $this->assertNull(Link::fromJson('[]'));
        $this->assertNull(Link::fromJson('{}'));
        $this->assertNull(Link::fromJson('{"alias": "name"}'));
        $this->assertNull(Link::fromJson('{"id": 123}'));
    }

}
