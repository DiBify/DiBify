<?php
/**
 * Created for DiBify
 * Date: 02.01.2020
 * @author Timur Kasumov (XAKEPEHOK)
 */

namespace DiBify\DiBify\Locker\Lock;


use DiBify\DiBify\Model\ModelInterface;
use DiBify\DiBify\Model\Reference;
use JsonSerializable;

class Lock implements JsonSerializable
{

    private Reference $locker;

    private ?string $identity;

    private ?int $timeout;

    /**
     * @param ModelInterface|Reference $lockerModelOrReference
     * @param string|null $identity
     * @param int|null $timeout
     */
    public function __construct($lockerModelOrReference, string $identity = null, int $timeout = null)
    {
        $this->locker = $lockerModelOrReference instanceof ModelInterface ? Reference::to($lockerModelOrReference) : $lockerModelOrReference;
        $this->timeout = $timeout;
        $this->identity = $identity;
    }

    public function getLocker(): Reference
    {
        return $this->locker;
    }

    public function getIdentity(): ?string
    {
        return $this->identity;
    }

    public function getTimeout(): ?int
    {
        return $this->timeout;
    }

    public function setTimeout(int $timeout): self
    {
        $clone = clone $this;
        $clone->timeout = $timeout;
        return $clone;
    }

    public function isCompatible(self $lock): bool
    {
        return $lock->locker === $this->locker && $lock->identity === $this->identity;
    }

    public function jsonSerialize(): array
    {
        return [
            'locker' => $this->locker,
            'identity' => $this->identity,
            'timeout' => $this->timeout,
        ];
    }
}