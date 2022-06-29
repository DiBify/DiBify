<?php
/**
 * Created for DiBify
 * Date: 03.01.2020
 * @author Timur Kasumov (XAKEPEHOK)
 */

namespace DiBify\DiBify\Locker\Lock;

use DiBify\DiBify\Mock\TestModel_1;
use DiBify\DiBify\Mock\TestModel_2;
use DiBify\DiBify\Model\ModelInterface;
use DiBify\DiBify\Model\Reference;
use JsonSerializable;
use PHPUnit\Framework\TestCase;

class LockTest extends TestCase
{

    private ModelInterface $locker;

    private int $timeout;

    private string $identity;

    private Lock $lock_1;
    private Lock $lock_2;

    protected function setUp(): void
    {
        parent::setUp();
        $this->locker = new TestModel_1(1);
        $this->identity = 'qwerty';
        $this->timeout = 10;
        $this->lock_1 = new Lock($this->locker, $this->identity, $this->timeout);
        $this->lock_2 = new Lock(Reference::to($this->locker));
    }

    public function testGetLocker(): void
    {
        $this->assertSame(Reference::to($this->locker), $this->lock_1->getLocker());
        $this->assertSame(Reference::to($this->locker), $this->lock_2->getLocker());
    }

    public function testGetIdentity(): void
    {
        $this->assertSame($this->identity, $this->lock_1->getIdentity());
        $this->assertNull($this->lock_2->getIdentity());
    }

    public function testGetTimeout(): void
    {
        $this->assertSame($this->timeout, $this->lock_1->getTimeout());
        $this->assertNull($this->lock_2->getTimeout());
    }

    public function testSetTimeout(): void
    {
        $newLock = $this->lock_1->setTimeout(60);
        $this->assertSame($this->timeout, $this->lock_1->getTimeout());

        $this->assertNotSame($this->lock_1, $newLock);
        $this->assertInstanceOf(Lock::class, $newLock);
        $this->assertSame(60, $newLock->getTimeout());
    }

    public function testIsCompatible(): void
    {
        $lock_1 = new Lock(new TestModel_1(1), $this->identity);
        $lock_2 = new Lock(new TestModel_2(2));

        $this->assertTrue($this->lock_1->isCompatible($lock_1));
        $this->assertFalse($this->lock_1->isCompatible($lock_2));
        $this->assertFalse($lock_1->isCompatible($lock_2));
    }

    public function testJsonSerialize(): void
    {
        $this->assertInstanceOf(JsonSerializable::class, $this->lock_1);
        $this->assertSame(json_encode([
            'locker' => [
                'alias' => TestModel_1::getModelAlias(),
                'id' => '1',
            ],
            'identity' => $this->identity,
            'timeout' => $this->timeout,
        ]), json_encode($this->lock_1));
    }
}
