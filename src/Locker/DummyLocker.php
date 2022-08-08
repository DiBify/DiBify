<?php


namespace DiBify\DiBify\Locker;


use DiBify\DiBify\Locker\Lock\Lock;
use DiBify\DiBify\Model\ModelInterface;
use SplObjectStorage;
use Throwable;

class DummyLocker implements LockerInterface
{

    use WaitForLockTrait;

    private SplObjectStorage $locks;
    private int $defaultTimeout;
    private array $onLockHandlers = [];
    private array $onUnlockHandlers = [];

    public function __construct(int $defaultTimeout = 60)
    {
        $this->locks = new SplObjectStorage();
        $this->defaultTimeout = $defaultTimeout;
    }

    /**
     * @param ModelInterface $model
     * @param Lock $lock
     * @param Throwable|null $throwable
     * @return bool
     * @throws Throwable
     */
    public function lock(ModelInterface $model, Lock $lock, ?Throwable $throwable = null): bool
    {
        $locked = $this->isLockedFor($model, $lock);
        if ($locked) {
            if ($throwable) {
                throw $throwable;
            }
            return false;
        }

        $this->lockModel($model, $lock);

        return true;
    }

    /**
     * @param ModelInterface $model
     * @param Lock $lock
     * @param Throwable|null $throwable
     * @return bool
     * @throws Throwable
     */
    public function unlock(ModelInterface $model, Lock $lock, ?Throwable $throwable = null): bool
    {
        $locked = $this->isLockedFor($model, $lock);
        if ($locked) {
            if ($throwable) {
                throw $throwable;
            }
            return false;
        }

        $this->unlockModel($model);
        return true;
    }

    /**
     * @param ModelInterface $model
     * @param Lock $currentLock
     * @param Lock $lock
     * @param Throwable|null $throwable
     * @return bool
     * @throws Throwable
     */
    public function passLock(ModelInterface $model, Lock $currentLock, Lock $lock, ?Throwable $throwable = null): bool
    {
        $locked = $this->isLockedFor($model, $currentLock);
        if ($locked) {
            if ($throwable) {
                throw $throwable;
            }
            return false;
        }

        $this->lockModel($model, $lock);
        return true;
    }

    public function isLockedFor(ModelInterface $model, Lock $lock): bool
    {
        if (!isset($this->locks[$model])) {
            return false;
        }

        $data = $this->locks[$model];

        if (time() > $data[1]) {
            $this->unlockModel($model);
            return false;
        }

        /** @var Lock $lock */
        $current = $data[0];

        return !$lock->isCompatible($current);
    }

    public function getLock(ModelInterface $model): ?Lock
    {
        $data = $this->locks[$model] ?? null;

        if (is_null($data)) {
            return null;
        }

        /** @var Lock $lock */
        $lock = $data[0];

        if (time() > $data[1]) {
            $this->unlockModel($model);
            return null;
        }

        return $lock->setTimeout(time() - $data[1]);
    }

    public function getDefaultTimeout(): int
    {
        return $this->defaultTimeout;
    }

    public function addOnLockHandler(callable $handler): void
    {
        $this->onLockHandlers[] = $handler;
    }

    public function addOnUnlockHandler(callable $handler): void
    {
        $this->onUnlockHandlers[] = $handler;
    }

    private function lockModel(ModelInterface $model, Lock $lock): void
    {
        $this->locks[$model] = [$lock, time() + ($lock->getTimeout() ?? $this->defaultTimeout)];
        foreach ($this->onLockHandlers as $onLockHandler) {
            $onLockHandler($model, $lock);
        }
    }

    private function unlockModel(ModelInterface $model): void
    {
        unset($this->locks[$model]);
        foreach ($this->onUnlockHandlers as $onUnlockHandler) {
            $onUnlockHandler($model);
        }
    }

}