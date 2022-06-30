<?php
/**
 * Created for DiBify
 * Date: 15.03.2020
 * @author Timur Kasumov (XAKEPEHOK)
 */

namespace DiBify\DiBify\Helpers;

use DiBify\DiBify\Id\Id;
use DiBify\DiBify\Mock\TestModel_1;
use DiBify\DiBify\Model\Reference;
use PHPUnit\Framework\TestCase;

class IdHelperTest extends TestCase
{

    public function idDataProvider(): array
    {
        return [
            [new TestModel_1(1), '1'],
            [Reference::create('model', 2), '2'],
            [new Id(3), '3'],
            [4, '4'],
        ];
    }

    /**
     * @dataProvider idDataProvider
     * @param $input
     * @param $output
     */
    public function testScalarizeOne($input, $output)
    {
        $this->assertSame(
            $output,
            IdHelper::scalarizeOne($input)
        );
    }

    /**
     * @dataProvider idDataProvider
     * @param $input
     * @param $output
     */
    public function testScalarizeMany($input, $output)
    {
        $this->assertSame(
            [$output],
            IdHelper::scalarizeMany(...([$input]))
        );
    }
}
