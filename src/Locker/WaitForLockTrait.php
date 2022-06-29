<?php
/**
 * Created for dibify
 * Date: 27.04.2021
 * @author Timur Kasumov (XAKEPEHOK)
 */

namespace DiBify\DiBify\Locker;


use DiBify\DiBify\Locker\Lock\Lock;
use DiBify\DiBify\Model\ModelInterface;

trait WaitForLockTrait
{

    public function waitForLock(array $models, int $waitTimeout, Lock $lock): bool
    {
        $started = time();
        do {
            $lockedCount = 0;
            foreach ($models as $model) {
                if ($this->lock($model, $lock)) {
                    $lockedCount++;
                }
            }

            if ($isLocked = count($models) === $lockedCount) {
                break;
            }

            sleep(1);
            $duration = time() - $started;
        } while ($duration < $waitTimeout);

        if (!$isLocked) {
            foreach ($models as $model) {
                $this->unlock($model, $lock);
            }
            return false;
        }

        return true;
    }

    abstract public function lock(ModelInterface $model, Lock $lock): bool;

    abstract public function unlock(ModelInterface $model, Lock $lock): bool;

}