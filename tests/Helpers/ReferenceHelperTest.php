<?php

namespace DiBify\DiBify\Helpers;

use DiBify\DiBify\Mock\TestModel_1;
use DiBify\DiBify\Mock\TestModel_2;
use DiBify\DiBify\Mock\TestModel_3;
use DiBify\DiBify\Model\ModelInterface;
use DiBify\DiBify\Model\Reference;
use PHPUnit\Framework\TestCase;

class ReferenceHelperTest extends TestCase
{

    public function testToMany(): void
    {
        $model_1 = new TestModel_1(1);
        $model_2 = new TestModel_2(2);
        $model_3 = new TestModel_3(3);

        $references = ReferenceHelper::toMany([$model_1, $model_2, $model_3, $model_1]);
        $this->assertCount(3, $references);
        $this->assertReference($model_1, $references[0]);
        $this->assertReference($model_2, $references[1]);
        $this->assertReference($model_3, $references[2]);

        $references = ReferenceHelper::toMany([$model_1, $model_2, $model_3, $model_1], [TestModel_2::class, TestModel_3::class]);
        $this->assertCount(2, $references);
        $this->assertReference($model_2, $references[0]);
        $this->assertReference($model_3, $references[1]);

        $references = ReferenceHelper::toMany([$model_1, $model_2, $model_3, $model_1], null, false);
        $this->assertCount(4, $references);
        $this->assertReference($model_1, $references[0]);
        $this->assertReference($model_2, $references[1]);
        $this->assertReference($model_3, $references[2]);
        $this->assertReference($model_1, $references[3]);
    }

    private function assertReference(ModelInterface $expected, Reference $actual): void
    {
        $this->assertTrue($actual->isFor($expected));
    }
}
