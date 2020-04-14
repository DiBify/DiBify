<?php
/**
 * @author Timur Kasumov (aka XAKEPEHOK)
 * Datetime: 30.09.2017 0:10
 */

namespace DiBify\DiBify\Helpers;


use ArrayObject;
use DiBify\DiBify\Id\Id;
use DiBify\DiBify\Mock\TestModel_1;
use DiBify\DiBify\Mock\TestModel_2;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class ReflectionHelperTest extends TestCase
{

    /** @var TestModel_1 */
    private $model;

    /** @var ArrayObject */
    private $arrayAccess;

    protected function setUp(): void
    {
        $this->model = new TestModel_1(1, 2);
        $this->arrayAccess = new ArrayObject(['key' => 'value']);
    }

    public function testNewWithoutConstructor()
    {
        /** @var TestModel_1 $model */
        $model = ReflectionHelper::newWithoutConstructor(TestModel_1::class);
        $this->assertInstanceOf(TestModel_1::class, $model);
        $this->assertEquals(null, $model->getOtherId());
    }

    public function testGetProperty()
    {
        $this->assertSame(
            $this->model->getOtherId(),
            ReflectionHelper::getProperty($this->model, 'otherId')
        );
    }

    public function testGetPropertyArrayAccess()
    {
        $this->assertSame(
            $this->arrayAccess['key'],
            ReflectionHelper::getProperty($this->arrayAccess, 'key')
        );
    }

    public function testSetProperty()
    {
        $id = new Id(3);
        ReflectionHelper::setProperty($this->model, 'otherId', $id);
        $this->assertSame(
            $id,
            $this->model->getOtherId()
        );
    }

    public function testSetPropertyArrayAccess()
    {
        $id = new Id(3);
        ReflectionHelper::setProperty($this->arrayAccess, 'otherId', $id);
        $this->assertSame(
            $id,
            $this->arrayAccess['otherId']
        );
    }

    public function testGetMethod()
    {
        $method_1 = ReflectionHelper::getMethod($this->model, 'onBeforeCommit');
        $this->assertInstanceOf(ReflectionMethod::class, $method_1);
        $this->assertEquals('onBeforeCommit', $method_1->getName());

        $method_2 = ReflectionHelper::getMethod(TestModel_2::class, 'onBeforeCommit');
        $this->assertInstanceOf(ReflectionMethod::class, $method_2);
        $this->assertEquals('onBeforeCommit', $method_2->getName());

        $method_3 = ReflectionHelper::getMethod(TestModel_1::class, 'onAfterCommit');
        $this->assertInstanceOf(ReflectionMethod::class, $method_3);
        $this->assertEquals('onAfterCommit', $method_3->getName());

        $this->assertNotSame($method_1, $method_2);
        $this->assertNotSame($method_1, $method_3);
    }
}
