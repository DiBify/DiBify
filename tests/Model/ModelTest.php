<?php
/**
 * @author Timur Kasumov (aka XAKEPEHOK)
 * Datetime: 20.06.2017 20:02
 */

namespace DiBify\DiBify\Model;


use DiBify\DiBify\Id\Id;
use DiBify\DiBify\Mock\TestModel_1;
use PHPUnit\Framework\TestCase;

class ModelTest extends TestCase
{

    public function testEmptyModelConstruct()
    {
        $model = new TestModel_1();
        self::assertInstanceOf(Id::class, $model->id());
        self::assertNull($model->getOtherId());
        self::assertFalse($model->id()->isAssigned());
        return $model;
    }

    public function testFilledModelConstruct()
    {
        $model = new TestModel_1(2,1);
        self::assertInstanceOf(Id::class, $model->id());
        self::assertInstanceOf(Id::class, $model->getOtherId());
        $this->assertEquals(2, (string) $model->id());
        self::assertTrue($model->id()->isAssigned());
        self::assertTrue($model->getOtherId()->isAssigned());
        return $model;
    }

    /**
     * @depends testEmptyModelConstruct
     * @param TestModel_1 $model
     */
    public function testSetOther(TestModel_1 $model)
    {
        $other = new TestModel_1();
        $model->setOtherModel($other);

        self::assertFalse($model->getOtherId()->isAssigned());
        self::assertEquals($other->id(), $model->getOtherId());

        $other->id()->assign(7);
        self::assertTrue($model->getOtherId()->isAssigned());
    }

}