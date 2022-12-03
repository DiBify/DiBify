<?php

namespace DiBify\DiBify\Id;

use DiBify\DiBify\Mock\TestModel_1;
use PHPUnit\Framework\TestCase;

class SortableUniqueIdGeneratorTest extends TestCase
{

    public function invokeDataProvider(): array
    {
        return [
            [16, '', '~^[\da-z]{16}$~', null],
            [32, '', '~^[\da-z]{32}$~', null],
            [10, '', '~^[\da-z]{10}$~', null],
            [17, '-', '~^[\da-z]{8}-[\da-z]{8}$~', null],
            [33, '-', '~^[\da-z]{8}-[\da-z]{24}$~', null],
            [11, '-', '~^[\da-z]{8}-[\da-z]{2}$~', null],
        ];
    }

    public function generateDataProvider(): array
    {
        return [
            [16, '', '~^lb8igo5c[\da-z]{8}$~', 1670106792.000],
            [32, '', '~^lb8igo5c[\da-z]{24}$~', 1670106792],
            [10, '', '~^lb8igo5c[\da-z]{2}$~', 1670106792.000],
            [17, '-', '~^lb8igo5c-[\da-z]{8}$~', 1670106792.000],
            [33, '-', '~^lb8igo5c-[\da-z]{24}$~', 1670106792.000],
            [11, '-', '~^lb8igo5c-[\da-z]{2}$~', 1670106792.000],
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
     * @dataProvider generateDataProvider
     * @param int $length
     * @param string $separator
     * @param string $pattern
     * @param float|null $timestamp
     * @return void
     */
    public function testGenerate(int $length, string $separator, string $pattern, ?float $timestamp): void
    {
        $this->assertMatchesRegularExpression(
            $pattern,
            SortableUniqueIdGenerator::generate($length, $separator, $timestamp)
        );
    }

    public function fixtureDataProvider(): array
    {
        return [
            [1670106792, 16, '', '~^lb8igo5clb8igo5c$~'],
            [1670106792, 32, '', '~^lb8igo5clb8igo5clb8igo5clb8igo5c$~'],
            [1670106792.000, 10, '', '~^lb8igo5clb$~'],
            [1670106792, 17, '-', '~^lb8igo5c-lb8igo5c$~'],
            [1670106792, 33, '-', '~^lb8igo5c-lb8igo5clb8igo5clb8igo5c$~'],
            [1670106792.000, 11, '-', '~^lb8igo5c-lb$~'],
        ];
    }

    /**
     * @dataProvider fixtureDataProvider
     * @param float $timestamp
     * @param int $length
     * @param string $separator
     * @param string $pattern
     * @return void
     */
    public function testFixture(float $timestamp, int $length, string $separator, string $pattern): void
    {
        $this->assertMatchesRegularExpression(
            $pattern,
            SortableUniqueIdGenerator::fixture($timestamp, $length, $separator)
        );
    }
}
