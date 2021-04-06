<?php
/**
 * Created for dibify
 * Date: 06.04.2021
 * @author Timur Kasumov (XAKEPEHOK)
 */

namespace DiBify\DiBify\Helpers;

use DiBify\DiBify\Exceptions\DuplicateModelException;
use DiBify\DiBify\Exceptions\NotPermanentIdException;
use DiBify\DiBify\Mock\TestModel_1;
use DiBify\DiBify\Mock\TestModel_2;
use DiBify\DiBify\Mock\TestModel_3;
use PHPUnit\Framework\TestCase;

class ModelHelperTest extends TestCase
{

    public function testIndexById()
    {
        $models = [
            new TestModel_1(10),
            new TestModel_1(5),
            new TestModel_2(2),
            new TestModel_3(3),
        ];

        $indexed = ModelHelper::indexById($models);
        $this->assertCount(4, $indexed);

        foreach ($indexed as $id => $model) {
            $this->assertEquals($id, (string) $model->id());
        }
    }

    public function testIndexByIdWithNonPermanentId()
    {
        $this->expectException(NotPermanentIdException::class);
        ModelHelper::indexById([
            new TestModel_1()
        ]);
    }

    public function testIndexByIdWithDuplicateId()
    {
        $this->expectException(DuplicateModelException::class);
        ModelHelper::indexById([
            new TestModel_1(1),
            new TestModel_1(1),
        ]);
    }
}
