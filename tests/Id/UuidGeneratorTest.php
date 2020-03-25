<?php
/**
 * @author Timur Kasumov (aka XAKEPEHOK)
 * Datetime: 07.08.2017 2:33
 */

namespace DiBify\DiBify\Id;

use DiBify\DiBify\Mock\TestModel_1;
use PHPUnit\Framework\TestCase;

class UuidGeneratorTest extends TestCase
{

    public function testInvoke()
    {
        $model = new TestModel_1();
        $generator = new UuidGenerator();

        //6a737b8b-12de-4396-ad46-b5774099a8b5
        $pattern = '~^[a-z\d]{8}-([a-z\d]{4}-){3}[a-z\d]{12}$~';

        $id = $generator($model);
        $this->assertRegExp($pattern, (string) $id);
    }

}
