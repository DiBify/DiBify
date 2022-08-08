<?php
/**
 * @author Timur Kasumov (aka XAKEPEHOK)
 * Datetime: 15.03.2017 15:12
 */

namespace DiBify\DiBify\Manager;

use DiBify\DiBify\Exceptions\DuplicateModelException;
use DiBify\DiBify\Exceptions\InvalidArgumentException;
use DiBify\DiBify\Exceptions\LockedModelException;
use DiBify\DiBify\Exceptions\ModelRefreshException;
use DiBify\DiBify\Exceptions\NotPermanentIdException;
use DiBify\DiBify\Exceptions\SerializerException;
use DiBify\DiBify\Exceptions\UnknownModelException;
use DiBify\DiBify\Id\Id;
use DiBify\DiBify\Locker\Lock\ServiceLock;
use DiBify\DiBify\Locker\Lock\Lock;
use DiBify\DiBify\Locker\LockerInterface;
use DiBify\DiBify\Model\ModelAfterCommitEventInterface;
use DiBify\DiBify\Model\ModelBeforeCommitEventInterface;
use DiBify\DiBify\Model\ModelInterface;
use DiBify\DiBify\Model\Reference;
use DiBify\DiBify\Repository\Repository;
use SplObjectStorage;
use Throwable;

class ModelManager
{
    private ConfigManager $configManager;

    /** @var Repository[] */
    private array $repositories;

    private LockerInterface $locker;

    /** @var callable */
    private $onBeforeCommit;

    /** @var callable */
    private $onAfterCommit;

    /** @var callable */
    private $onCommitException;

    private static self $instance;

    private function __construct(
        ConfigManager $configManager,
        LockerInterface $locker,
        ?callable $onBeforeCommit,
        ?callable $onAfterCommit,
        ?callable $onCommitException
    )
    {
        $this->configManager = $configManager;
        $this->locker = $locker;

        $this->onBeforeCommit = $onBeforeCommit ?? function () {};
        $this->onAfterCommit = $onAfterCommit ?? function () {};
        $this->onCommitException = $onCommitException ?? function () {};
    }

    public static function construct(
        ConfigManager $configManager,
        LockerInterface $locker,
        callable $onBeforeCommit = null,
        callable $onAfterCommit = null,
        callable $onCommitException = null
    ): self
    {
        static::$instance = new self(
            $configManager,
            $locker,
            $onBeforeCommit,
            $onAfterCommit,
            $onCommitException
        );
        return static::$instance;
    }

    public function getLocker(): LockerInterface
    {
        return $this->locker;
    }

    /**
     * @param ModelInterface|Reference|string $modelObjectOrClassOrAlias
     * @return Repository
     * @throws InvalidArgumentException
     * @throws UnknownModelException
     */
    public function getRepository(ModelInterface|Reference|string $modelObjectOrClassOrAlias): Repository
    {
        $repo = $this->configManager->getRepository($modelObjectOrClassOrAlias);
        $this->repositories[get_class($repo)] = $repo;
        return $repo;
    }

    /**
     * @param Reference $reference
     * @return ModelInterface|null
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
     */
    public static function findByReferences(Reference ...$references): SplObjectStorage
    {
        $instance = static::$instance;
        $groups = [];
        foreach ($references as $reference) {
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
    public function findByAnyTypeId(ModelInterface|Reference|Id|string|int $argument, string $modelAliasOrClass = null): ?ModelInterface
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
     * @param ModelInterface $model
     * @return ModelInterface
     * @throws DuplicateModelException
     * @throws InvalidArgumentException
     * @throws ModelRefreshException
     * @throws NotPermanentIdException
     * @throws SerializerException
     * @throws UnknownModelException
     */
    public function refreshOne(ModelInterface $model): ModelInterface
    {
        $repo = $this->getRepository($model);
        $changes = $repo->refresh($model);

        if (!isset($changes[$model])) {
            throw new ModelRefreshException('Model was not refreshed, because it was not found (may be was deleted)');
        }

        return $changes[$model];
    }

    /**
     * @param ModelInterface ...$models
     * @return SplObjectStorage
     * @throws DuplicateModelException
     * @throws InvalidArgumentException
     * @throws NotPermanentIdException
     * @throws SerializerException
     * @throws UnknownModelException
     */
    public function refreshMany(ModelInterface ...$models): SplObjectStorage
    {
        $groups = [];
        foreach ($models as $model) {
            $groups[get_class($model)][] = $model;
        }

        $changes = new SplObjectStorage();

        foreach ($groups as $class => $models) {
            $changes->addAll($this->getRepository($class)->refresh(...$models));
        }

        return $changes;
    }

    /**
     * @param Transaction $transaction
     * @param Lock|null $lock
     * @throws InvalidArgumentException
     * @throws LockedModelException
     * @throws Throwable
     * @throws UnknownModelException
     * @throws DuplicateModelException
     * @throws NotPermanentIdException
     * @throws SerializerException
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
            if ($model instanceof ModelBeforeCommitEventInterface) {
                $model->onBeforeCommit();
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
                if ($model instanceof ModelAfterCommitEventInterface) {
                    $model->onAfterCommit();
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
        Transaction::freeUpMemory();
        foreach ($this->repositories as $repository) {
            $repository->freeUpMemory();
        }
    }

    /**
     * @param ModelInterface[] $models
     * @param Lock|null $lock
     * @throws LockedModelException
     */
    protected function lock(array $models, ?Lock $lock): void
    {
        $locker = $this->getLocker();
        foreach ($models as $model) {
            $currentLock = $locker->getLock($model);

            if ($currentLock && !$lock) {
                throw new LockedModelException('Model locked by somebody else');
            }

            if ($lock && !$locker->lock($model, $lock)) {
                throw new LockedModelException('Model locked by somebody else');
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
                $this->getLocker()->unlock($model, $lock);
            }
        }
    }

}