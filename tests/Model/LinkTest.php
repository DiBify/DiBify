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
        $this->model = new TestModel_1(1);
        $this->link = Link::to($this->model);
    }

    public function testConstructFromNameAndId()
    {
        $id = new Id(1);
        $pointer = new Link(TestModel_1::getModelName(), $id);
        $this->assertSame($id, $pointer->id());
    }

    public function testConstructFromClassAndId()
    {
        $link = new Link(TestModel_1::class, new Id(1));
        $this->assertEquals(TestModel_1::getModelName(), $link->getModelName());
    }

    public function testConstructScalarId()
    {
        $link = new Link(TestModel_1::class, 1);
        $this->assertEquals(TestModel_1::getModelName(), $link->getModelName());
    }

    public function testId()
    {
        $this->assertSame($this->model->id(), $this->link->id());
    }

    public function testGetModelName()
    {
        $this->assertEquals($this->model::getModelName(), $this->link->getModelName());
    }

    public function testToJson()
    {
        $expected = json_encode([
            'model' => $this->model::getModelName(),
            'id' => (string) $this->model->id(),
        ]);
        $this->assertEquals($expected, json_encode($this->link));
    }

    public function testFromJson()
    {
        $json = json_encode($this->link);
        $link = Link::fromJson($json);
        $this->assertEquals($this->link, $link);
    }

    public function testFromJsonNullOrFail()
    {
        $this->assertNull(Link::fromJson(''));
        $this->assertNull(Link::fromJson('null'));
        $this->assertNull(Link::fromJson('[]'));
        $this->assertNull(Link::fromJson('{}'));
        $this->assertNull(Link::fromJson('{"model": "name"}'));
        $this->assertNull(Link::fromJson('{"id": 123}'));
    }

}
