<?php
/**
 * Created for DiBify
 * Date: 02.01.2020
 * @author Timur Kasumov (XAKEPEHOK)
 */

namespace DiBify\DiBify\Locker\Lock;


use DiBify\DiBify\Model\ModelInterface;

class Lock
{

    private ModelInterface $locker;

    private ?int $timeout;

    public function __construct(ModelInterface $locker, int $timeout = null)
    {
        $this->locker = $locker;
        $this->timeout = $timeout;
    }

    public function getLocker(): ModelInterface
    {
        return $this->locker;
    }

    public function getTimeout(): ?int
    {
        return $this->timeout;
    }

}