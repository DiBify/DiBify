<?php


namespace DiBify\DiBify\Locker;


use DiBify\DiBify\Exceptions\InvalidArgumentException;
use DiBify\DiBify\Model\Reference;
use DiBify\DiBify\Model\ModelInterface;

class DummyLocker implements LockerInterface
{

    /** @var ModelInterface[][] */
    private array $locks = [];

    public function lock(ModelInterface $model, ModelInterface $locker, int $timeout = null): bool
    {
        $locked = $this->isLockedFor($model, $locker);
        if ($locked) {
            return false;
        }

        $name = $model::getModelAlias();
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

        $name = $model::getModelAlias();
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

        $name = $model::getModelAlias();
        $id = (string) $model->id();

        $this->locks[$name][$id] = $nextLocker;
        return true;
    }

    public function isLockedFor(ModelInterface $model, ModelInterface $locker): bool
    {
        $name = $model::getModelAlias();
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
    public function getLocker($modelOrReference): ?Reference
    {
        if ($modelOrReference instanceof ModelInterface) {
            $name = $modelOrReference::getModelAlias();
            $id = (string) $modelOrReference->id();
        }

        if ($modelOrReference instanceof Reference) {
            $name = $modelOrReference->getModelAlias();
            $id = (string) $modelOrReference->id();
        }

        if (isset($name) && isset($id)) {
            if (isset($this->locks[$name][$id])) {
                return Reference::to($this->locks[$name][$id]);
            }
            return null;
        }

        throw new InvalidArgumentException("Locker can be resolved by model or reference");
    }

    public function getDefaultTimeout(): int
    {
        return 10;
    }

}