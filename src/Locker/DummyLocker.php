<?php


namespace DiBify\DiBify\Locker;


use DiBify\DiBify\Exceptions\InvalidArgumentException;
use DiBify\DiBify\Model\Reference;
use DiBify\DiBify\Model\ModelInterface;
use SplObjectStorage;

class DummyLocker implements LockerInterface
{

    private SplObjectStorage $locks;
    private int $defaultTimeout;
    private array $onLockHandlers = [];
    private array $onUnlockHandlers = [];

    public function __construct(int $defaultTimeout = 60)
    {
        $this->locks = new SplObjectStorage();
        $this->defaultTimeout = $defaultTimeout;
    }

    public function lock(ModelInterface $model, ModelInterface $locker, int $timeout = null): bool
    {
        $locked = $this->isLockedFor($model, $locker);
        if ($locked) {
            return false;
        }

        $this->lockModel($model, $locker, $timeout);

        return true;
    }

    public function unlock(ModelInterface $model, ModelInterface $locker): bool
    {
        $locked = $this->isLockedFor($model, $locker);
        if ($locked) {
            return false;
        }

        $this->unlockModel($model);
        return true;
    }

    public function passLock(ModelInterface $model, ModelInterface $currentLocker, ModelInterface $nextLocker, int $timeout = null): bool
    {
        $locked = $this->isLockedFor($model, $currentLocker);
        if ($locked) {
            return false;
        }

        $this->lockModel($model, $nextLocker, $timeout);
        return true;
    }

    public function isLockedFor(ModelInterface $model, ModelInterface $locker): bool
    {
        if (!isset($this->locks[$model])) {
            return false;
        }

        $data = $this->locks[$model];

        if (time() > $data[1]) {
            $this->unlockModel($model);
            return false;
        }

        $currentLocker = $data[0];
        return $currentLocker !== $locker;
    }

    /**
     * @inheritDoc
     * @throws InvalidArgumentException
     */
    public function getLocker($modelOrReference): ?Reference
    {
        $reference = null;
        if ($modelOrReference instanceof ModelInterface) {
            $reference = Reference::to($modelOrReference);
        }

        if ($modelOrReference instanceof Reference) {
            $reference = $modelOrReference;
        }

        if (!is_null($reference)) {
            $model = $reference->getModel();
            if (isset($this->locks[$model])) {
                $data = $this->locks[$model];
                if (time() > $data[1]) {
                    $this->unlockModel($model);
                    return null;
                }
                return Reference::to($data[0]);
            }
            return null;
        }

        throw new InvalidArgumentException("Locker can be resolved by model or reference");
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

    private function lockModel(ModelInterface $model, ModelInterface $locker, int $timeout = null): void
    {
        if (is_null($timeout)) {
            $timeout = $this->getDefaultTimeout();
        }

        $this->locks[$model] = [$locker, time() + $timeout];
        foreach ($this->onLockHandlers as $onLockHandler) {
            $onLockHandler($model, $locker);
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