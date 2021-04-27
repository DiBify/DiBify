<?php
/**
 * Created for dibify
 * Date: 27.04.2021
 * @author Timur Kasumov (XAKEPEHOK)
 */

namespace DiBify\DiBify\Locker;


use DiBify\DiBify\Model\ModelInterface;

trait WaitForLockTrait
{

    public function waitForLock(array $models, ModelInterface $locker, int $waitTimeout, int $lockTimeout = null): bool
    {
        $started = time();
        do {
            $lockedCount = 0;
            foreach ($models as $model) {
                if ($this->lock($model, $locker, $lockTimeout)) {
                    $lockedCount++;
                }
            }
            sleep(1);
            $isLocked = count($models) === $lockedCount;
            $duration = time() - $started;
        } while (!$isLocked && $duration < $waitTimeout);

        if (!$isLocked) {
            foreach ($models as $model) {
                $this->unlock($model, $locker);
            }
            return false;
        }

        return true;
    }

    abstract public function lock(ModelInterface $model, ModelInterface $locker, int $timeout = null): bool;

    abstract public function unlock(ModelInterface $model, ModelInterface $locker): bool;

}