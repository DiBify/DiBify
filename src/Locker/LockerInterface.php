<?php


namespace DiBify\DiBify\Locker;


use DiBify\DiBify\Model\ModelInterface;
use DiBify\DiBify\Model\Reference;

interface LockerInterface
{

    /**
     * @param ModelInterface $model
     * @param ModelInterface $locker
     * @param int|null $timeout
     * @return bool
     */
    public function lock(ModelInterface $model, ModelInterface $locker, int $timeout = null): bool;

    /**
     * @param ModelInterface $model
     * @param ModelInterface $locker
     * @return bool
     */
    public function unlock(ModelInterface $model, ModelInterface $locker): bool;

    /**
     * @param ModelInterface $model
     * @param ModelInterface $currentLocker
     * @param ModelInterface $nextLocker
     * @param int|null $timeout
     * @return bool
     */
    public function passLock(ModelInterface $model, ModelInterface $currentLocker, ModelInterface $nextLocker, int $timeout = null): bool;

    /**
     * @param ModelInterface[] $models
     * @param ModelInterface $locker
     * @param int $waitTimeout
     * @param int|null $lockTimeout
     * @return bool
     */
    public function waitForLock(array $models, ModelInterface $locker, int $waitTimeout, int $lockTimeout = null): bool;

    /**
     * @param ModelInterface $model
     * @param ModelInterface $locker
     * @return bool
     */
    public function isLockedFor(ModelInterface $model, ModelInterface $locker): bool;

    /**
     * @param ModelInterface|Reference $modelOrReference
     * @return Reference|null
     */
    public function getLocker($modelOrReference): ?Reference;

    /**
     * @return int
     */
    public function getDefaultTimeout(): int;

}
