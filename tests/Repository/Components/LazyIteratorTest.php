<?php
/**
 * Created for dibify
 * Date: 25.01.2021
 * @author Timur Kasumov (XAKEPEHOK)
 */

namespace DiBify\DiBify\Repository\Components;

use DiBify\DiBify\Mock\TestModel_1;
use PHPUnit\Framework\TestCase;

class LazyIteratorTest extends TestCase
{

    private $models = [];

    private $fetcher;

    protected function setUp(): void
    {
        parent::setUp();
        $this->models = [
            '1' => [
                new TestModel_1(1),
                new TestModel_1(2),
                new TestModel_1(3),
            ],
            '2' => [
                new TestModel_1(4),
                new TestModel_1(5),
                new TestModel_1(6),
            ],
            '3' => [
                new TestModel_1(6),
                new TestModel_1(7),
            ],
        ];

        $this->fetcher = function (Pagination $pagination) {
            $this->assertSame(3, $pagination->getSize());
            return $this->models[$pagination->getNumber()] ?? [];
        };
    }

    public function testIteratorWithOverlay(): void
    {
        $iterator = new LazyIterator($this->fetcher, 3);

        $expectedModels = array_merge(
            $this->models['1'],
            $this->models['2'],
            $this->models['3'],
        );

        unset($expectedModels[6]);
        $expectedModels = array_values($expectedModels);

        $actualModels = [];
        foreach ($iterator as $id => $model) {
            $actualModels[] = $model;
            $this->assertEquals((string) $id, (string) $model->id());
        }

        $this->assertSame($expectedModels, $actualModels);
    }

    public function testIteratorWithoutOverlay(): void
    {
        $iterator = new LazyIterator($this->fetcher, 3, false);

        $expectedModels = array_merge(
            $this->models['1'],
            $this->models['2'],
            $this->models['3'],
        );

        $actualModels = [];
        foreach ($iterator as $id => $model) {
            $actualModels[] = $model;
            $this->assertEquals((string) $id, (string) $model->id());
        }

        $this->assertSame($expectedModels, $actualModels);
    }

    public function testIteratorWithEvents(): void
    {
        $iterator = new LazyIterator($this->fetcher, 3, false);

        $beforeCounter = 0;
        $iterator->setOnBeforeBatch(function () use (&$beforeCounter) {
            $beforeCounter++;
        });

        $afterModels = [];
        $iterator->setOnAfterBatch(function (array $models) use (&$afterModels) {
            $afterModels[] = $models;
        });

        foreach ($iterator as $id => $model) {
            $this->assertEquals((string) $id, (string) $model->id());
        }

        $this->assertEquals(4, $beforeCounter);
        $this->assertSame(array_values($this->models), $afterModels);
    }

}
