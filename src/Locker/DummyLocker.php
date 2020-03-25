<?php


namespace DiBify\DiBify\Locker;


use DiBify\DiBify\Exceptions\InvalidArgumentException;
use DiBify\DiBify\Model\Link;
use DiBify\DiBify\Model\ModelInterface;

class DummyLocker implements LockerInterface
{

    /** @var ModelInterface[][] */
    private $locks = [];

    public function lock(ModelInterface $model, ModelInterface $locker, int $timeout = null): bool
    {
        $locked = $this->isLockedFor($model, $locker);
        if ($locked) {
            return false;
        }

        $name = $model::getModelName();
        $id = (string) $model->id();

        $this->locks[$name][$id] = $locker;
        return true;
    }

    public function unlock(ModelInterface $model, ModelInterface $locker): bool
    {
        $locked = $this->isLockedFor($model, $locker);
        if ($locked) {
            return false;
        }

        $name = $model::getModelName();
        $id = (string) $model->id();

        unset($this->locks[$name][$id]);
        return true;
    }

    public function passLock(ModelInterface $model, ModelInterface $currentLocker, ModelInterface $nextLocker, int $timeout = null): bool
    {
        $locked = $this->isLockedFor($model, $currentLocker);
        if ($locked) {
            return false;
        }

        $name = $model::getModelName();
        $id = (string) $model->id();

        $this->locks[$name][$id] = $nextLocker;
        return true;
    }

    public function isLockedFor(ModelInterface $model, ?ModelInterface $locker): bool
    {
        $name = $model::getModelName();
        $id = (string) $model->id();

        if (!isset($this->locks[$name][$id])) {
            return false;
        }

        $currentLocker = $this->locks[$name][$id];
        return $currentLocker !== $locker;
    }

    /**
     * @inheritDoc
     * @throws InvalidArgumentException
     */
    public function getLocker($modelOrLink): ?Link
    {
        if ($modelOrLink instanceof ModelInterface) {
            $name = $modelOrLink::getModelName();
            $id = (string) $modelOrLink->id();
        }

        if ($modelOrLink instanceof Link) {
            $name = $modelOrLink->getModelName();
            $id = (string) $modelOrLink->id();
        }

        if (isset($name) && isset($id)) {
            if (isset($this->locks[$name][$id])) {
                return Link::to($this->locks[$name][$id]);
            }
            return null;
        }

        throw new InvalidArgumentException("Locker can be resolved by model or link");
    }

    public function getDefaultTimeout(): int
    {
        return 10;
    }

}