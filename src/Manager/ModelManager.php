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
use DiBify\DiBify\Exceptions\NotModelInterfaceException;
use DiBify\DiBify\Repository\Repository;
use SplObjectStorage;
use Throwable;

class ModelManager
{
    /**
     * @var ModelInterface[]
     */
    protected $persisted = [];
    /**
     * @var ModelInterface[]
     */
    protected $deleted = [];
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
        foreach ($groups as $modelName => $indexedReferences) {
            $repo = $instance->getRepository($modelName);
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
     * Prepare model to store it in DB
     * @param ModelInterface|ModelInterface[] $models
     * @throws NotModelInterfaceException
     */
    public function persists($models = []): void
    {
        if (!is_array($models)) {
            $models = func_get_args();
        }

        foreach ($models as $model) {
            $this->guardNotModelInterface($model);
            $hash = spl_object_hash($model);
            $this->persisted[$hash] = $model;
            unset($this->deleted[$hash]);
        }
    }

    public function isPersisted(ModelInterface $model): bool
    {
        $hash = spl_object_hash($model);
        return isset($this->persisted[$hash]);
    }

    public function resetPersisted(): void
    {
        $this->persisted = [];
    }

    /**
     * Prepare models for delete it from DB
     * @param ModelInterface|ModelInterface[] $models
     * @throws NotModelInterfaceException
     */
    public function delete($models = []): void
    {
        if (!is_array($models)) {
            $models = func_get_args();
        }

        foreach ($models as $model) {
            $this->guardNotModelInterface($model);
            $hash = spl_object_hash($model);
            $this->deleted[$hash] = $model;
            unset($this->persisted[$hash]);
        }
    }

    public function isDeleted(ModelInterface $model): bool
    {
        $hash = spl_object_hash($model);
        return isset($this->deleted[$hash]);
    }

    public function resetDeleted(): void
    {
        $this->deleted = [];
    }

    /**
     * @param Lock $lock
     * @return Commit
     * @throws InvalidArgumentException
     * @throws NotModelInterfaceException
     * @throws Throwable
     * @throws UnknownModelException
     */
    public function commit(Lock $lock = null): Commit
    {
        $commit = new Commit($this->persisted, $this->deleted);

        //Assigns real ids
        foreach ($commit->getPersisted() as $model) {
            $idGenerator = $this->configManager->getIdGenerator($model);
            $idGenerator($model);
        }

        //onBeforeCommit
        foreach ($commit->getPersisted() as $model) {
            if (method_exists($model, 'onBeforeCommit')) {
                $this->runModelEvent($model, 'onBeforeCommit');
            }
        }

        ($this->onBeforeCommit)($commit);
        $models = array_merge($commit->getPersisted(), $commit->getDeleted());

        try {
            $this->lock($models, $lock);

            $modelClasses = array_unique(array_map('get_class',$models));
            foreach ($modelClasses as $modelClass) {
                $this->getRepository($modelClass)->commit($commit);
            }

            ($this->onAfterCommit)($commit);

            //onAfterCommit
            foreach ($commit->getPersisted() as $model) {
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

            ($this->onCommitException)($commit);
            throw $exception;
        }

        return $commit;
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
     * @param Lock $lock
     * @throws LockedModelException
     */
    protected function lock(array $models, ?Lock $lock): void
    {
        if ($lock) {
            foreach ($models as $model) {
                if ($this->getLocker()->isLockedFor($model, $lock->getLocker())) {
                    throw new LockedModelException();
                }

                $this->getLocker()->lock($model, $lock->getLocker(), $lock->getTimeout());
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

    /**
     * @param $model
     * @throws NotModelInterfaceException
     */
    private function guardNotModelInterface($model): void
    {
        if (!$model instanceof ModelInterface) {
            throw new NotModelInterfaceException($model);
        }
    }

}