<?php


namespace DiBify\DiBify\Locker;


use DiBify\DiBify\Model\ModelInterface;
use DiBify\DiBify\Model\Link;

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
     * @param ModelInterface $model
     * @param ModelInterface|null $locker
     * @return bool
     */
    public function isLockedFor(ModelInterface $model, ?ModelInterface $locker): bool;

    /**
     * @param ModelInterface|Link $modelOrLink
     * @return Link|null
     */
    public function getLocker($modelOrLink): ?Link;

    /**
     * @return int
     */
    public function getDefaultTimeout(): int;

}
