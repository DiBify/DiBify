<?php
/**
 * Created for DiBify
 * Date: 03.01.2020
 * @author Timur Kasumov (XAKEPEHOK)
 */

namespace DiBify\DiBify\Locker\Lock;

use DiBify\DiBify\Mock\TestModel_1;
use DiBify\DiBify\Model\ModelInterface;
use PHPUnit\Framework\TestCase;

class LockTest extends TestCase
{

    /** @var ModelInterface */
    private $locker;

    /** @var int */
    private $timeout;

    /** @var Lock */
    private $lock;

    protected function setUp(): void
    {
        parent::setUp();
        $this->locker = new TestModel_1(1);
        $this->timeout = 10;
        $this->lock = new Lock($this->locker, $this->timeout);
    }

    public function testGetLocker()
    {
        $this->assertSame($this->locker, $this->lock->getLocker());
    }

    public function testGetTimeout()
    {
        $this->assertSame($this->timeout, $this->lock->getTimeout());
    }
}
