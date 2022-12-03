<?php

namespace DiBify\DiBify\Id;

use DiBify\DiBify\Mock\TestModel_1;
use PHPUnit\Framework\TestCase;

class SortableUniqueIdGeneratorTest extends TestCase
{

    public function invokeDataProvider(): array
    {
        return [
            [16, '', '~^[\da-z]{16}$~'],
            [32, '', '~^[\da-z]{32}$~'],
            [10, '', '~^[\da-z]{10}$~'],
            [17, '-', '~^[\da-z]{8}-[\da-z]{8}$~'],
            [33, '-', '~^[\da-z]{8}-[\da-z]{24}$~'],
            [11, '-', '~^[\da-z]{8}-[\da-z]{2}$~'],
        ];
    }

    /**
     * @dataProvider invokeDataProvider
     * @param int $length
     * @param string $separator
     * @param string $pattern
     * @return void
     */
    public function testInvoke(int $length, string $separator, string $pattern): void
    {
        $model = new TestModel_1();
        $generator = new SortableUniqueIdGenerator($length, $separator);
        $id = $generator($model);
        $this->assertMatchesRegularExpression($pattern, (string) $id);
    }

    /**
     * @dataProvider invokeDataProvider
     * @param int $length
     * @param string $separator
     * @param string $pattern
     * @return void
     */
    public function testGenerate(int $length, string $separator, string $pattern): void
    {
        $this->assertMatchesRegularExpression(
            $pattern,
            SortableUniqueIdGenerator::generate($length, $separator)
        );
    }
}
