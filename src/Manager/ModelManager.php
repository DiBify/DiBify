<?php
/**
 * @author Timur Kasumov (aka XAKEPEHOK)
 * Datetime: 15.03.2017 15:12
 */

namespace DiBify\DiBify\Manager;

use DiBify\DiBify\Exceptions\InvalidArgumentException;
use DiBify\DiBify\Exceptions\LockedModelException;
use DiBify\DiBify\Exceptions\UnknownModelException;
use DiBify\DiBify\Helpers\ReflectionHelper;
use DiBify\DiBify\Id\Id;
use DiBify\DiBify\Locker\Lock\ServiceLock;
use DiBify\DiBify\Locker\Lock\Lock;
use DiBify\DiBify\Locker\LockerInterface;
use DiBify\DiBify\Model\ModelInterface;
use DiBify\DiBify\Model\Reference;
use DiBify\DiBify\Repository\Repository;
use SplObjectStorage;
use Throwable;

class ModelManager
{
    /**
     * @var ConfigManager
     */
    private $configManager;
    /**
     * @var Repository[]
     */
    private $repositories;
    /**
     * @var LockerInterface
     */
    private $locker;
    /**
     * @var callable
     */
    private $onBeforeCommit;
    /**
     * @var callable
     */
    private $onAfterCommit;
    /**
     * @var callable
     */
    private $onCommitException;
    /**
     * @var static
     */
    private static $instance;

    public function __construct(
        ConfigManager $configManager,
        LockerInterface $locker,
        callable $onBeforeCommit = null,
        callable $onAfterCommit = null,
        callable $onCommitException = null
    )
    {
        $this->configManager = $configManager;
        $this->locker = $locker;

        $this->onBeforeCommit = $onBeforeCommit ?? function () {};
        $this->onAfterCommit = $onAfterCommit ?? function () {};
        $this->onCommitException = $onCommitException ?? function () {};

        static::$instance = $this;
    }

    public function getLocker(): LockerInterface
    {
        return $this->locker;
    }

    /**
     * @param $modelObjectOrClassOrAlias
     * @return Repository
     * @throws InvalidArgumentException
     * @throws UnknownModelException
     */
    public function getRepository($modelObjectOrClassOrAlias): Repository
    {
        $repo = $this->configManager->getRepository($modelObjectOrClassOrAlias);
        $this->repositories[get_class($repo)] = $repo;
        return $repo;
    }

    /**
     * @param Reference $reference
     * @return ModelInterface|null
     * @throws InvalidArgumentException
     * @throws UnknownModelException
     */
    public static function findByReference(Reference $reference): ?ModelInterface
    {
        $instance = static::$instance;
        $repo = $instance->getRepository($reference->getModelAlias());
        return $repo->findById($reference->id());
    }

    /**
     * @param Reference[] $references
     * @return SplObjectStorage
     * @throws InvalidArgumentException
     * @throws UnknownModelException
     */
    public static function findByReferences(array $references): SplObjectStorage
    {
        $instance = static::$instance;
        $groups = [];
        foreach ($references as $reference) {
            if (!($reference instanceof Reference)) {
                throw new InvalidArgumentException("Every reference should be instance of " . Reference::class, 1);
            }
            $alias = $reference->getModelAlias();
            $id = (string) $reference->id();
            $groups[$alias][$id] = $reference;
        }

        $result = new SplObjectStorage();
        foreach ($groups as $alias => $indexedReferences) {
            $repo = $instance->getRepository($alias);
            $models = $repo->findByIds(array_unique(array_keys($indexedReferences)));
            foreach ($models as $model) {
                foreach ($indexedReferences as $reference) {
                    if ($reference->isFor($model)) {
                        $result[$reference] = $model;
                    }
                }
            }
        }
        return $result;
    }

    /**
     * @param ModelInterface|Reference|Id|string|int $argument
     * @param string|null $modelAliasOrClass
     * @return ModelInterface|null
     * @throws InvalidArgumentException
     * @throws UnknownModelException
     */
    public function findByAnyTypeId($argument, string $modelAliasOrClass = null): ?ModelInterface
    {
        if ($argument instanceof ModelInterface) {
            return $argument;
        }

        if ($argument instanceof Reference) {
            return $this->findByReference($argument);
        }

        $repo = $this->getRepository($modelAliasOrClass);
        return $repo->findById($argument);
    }

    /**
     * @param Transaction $transaction
     * @param Lock $lock
     * @throws InvalidArgumentException
     * @throws Throwable
     * @throws UnknownModelException
     */
    public function commit(Transaction $transaction, Lock $lock = null): void
    {
        //Assigns real ids
        foreach ($transaction->getPersisted() as $model) {
            $idGenerator = $this->configManager->getIdGenerator($model);
            $idGenerator($model);
        }

        //onBeforeCommit
        foreach ($transaction->getPersisted() as $model) {
            if (method_exists($model, 'onBeforeCommit')) {
                $this->runModelEvent($model, 'onBeforeCommit');
            }
        }

        ($this->onBeforeCommit)($transaction);
        $models = array_merge($transaction->getPersisted(), $transaction->getDeleted());

        try {
            $this->lock($models, $lock);

            $modelClasses = array_unique(array_map('get_class',$models));
            foreach ($modelClasses as $modelClass) {
                $this->getRepository($modelClass)->commit($transaction);
            }

            ($this->onAfterCommit)($transaction);

            //onAfterCommit
            foreach ($transaction->getPersisted() as $model) {
                if (method_exists($model, 'onAfterCommit')) {
                    $this->runModelEvent($model, 'onAfterCommit');
                }
            }

            if ($lock instanceof ServiceLock) {
                $this->unlock($models, $lock);
            }

        } catch (Throwable $exception) {

            if ($lock instanceof ServiceLock) {
                $this->unlock($models, $lock);
            }

            ($this->onCommitException)($transaction);
            throw $exception;
        }
    }

    /**
     * FreeUp all preloaded models in all repositories and all references
     */
    public function freeUpMemory(): void
    {
        Reference::freeUpMemory();
        foreach ($this->repositories as $repository) {
            $repository->freeUpMemory();
        }
    }

    protected function runModelEvent(ModelInterface $model, string $method)
    {
        $method = ReflectionHelper::getMethod($model, $method);
        $method->setAccessible(true);
        $method->invoke($model);
    }

    /**
     * @param ModelInterface[] $models
     * @param Lock|null $lock
     * @throws LockedModelException
     */
    protected function lock(array $models, ?Lock $lock): void
    {
        if ($lock) {
            $locker = $this->getLocker();
            foreach ($models as $model) {

                if ($locker->isLockedFor($model, $lock->getLocker())) {
                    throw new LockedModelException('Model locked by somebody else');
                }

                if ($locker->getLocker($model) === null) {
                    $locker->lock($model, $lock->getLocker(), $lock->getTimeout());
                }
            }
        }
    }

    /**
     * @param ModelInterface[] $models
     * @param Lock|null $lock
     */
    protected function unlock(array $models, ?Lock $lock): void
    {
        if ($lock) {
            foreach ($models as $model) {
                $this->getLocker()->unlock($model, $lock->getLocker());
            }
        }
    }

}