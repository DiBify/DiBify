<?php
/**
 * @author Timur Kasumov (aka XAKEPEHOK)
 * Datetime: 15.03.2017 15:10
 */

namespace DiBify\DiBify\Repository;

use DiBify\DiBify\Exceptions\DuplicateModelException;
use DiBify\DiBify\Exceptions\UnassignedIdException;
use DiBify\DiBify\Exceptions\SerializerException;
use DiBify\DiBify\Helpers\IdHelper;
use DiBify\DiBify\Helpers\ModelHelper;
use DiBify\DiBify\Id\Id;
use DiBify\DiBify\Manager\Transaction;
use DiBify\DiBify\Mappers\MapperInterface;
use DiBify\DiBify\Model\Reference;
use DiBify\DiBify\Model\ModelInterface;
use DiBify\DiBify\Replicator\ReplicatorInterface;
use DiBify\DiBify\Repository\Storage\StorageData;
use Exception;
use SplObjectStorage;

abstract class Repository
{

    /** @var ModelInterface[][] */
    protected array $registered = [];

    protected ReplicatorInterface $replicator;

    public function __construct(ReplicatorInterface $replicator)
    {
        $this->replicator = $replicator;
    }

    public function getReplicator(): ReplicatorInterface
    {
        return $this->replicator;
    }

    /**
     * @param Id|int|string $id
     * @param Exception|null $notFoundException
     * @return ModelInterface|null
     * @throws Exception
     */
    public function findById($id, Exception $notFoundException = null): ?ModelInterface
    {
        $scalarId = IdHelper::scalarizeOne($id);
        $registered = $this->getRegisteredByIds($scalarId);

        if (isset($registered[$scalarId])) {
            return $registered[$scalarId];
        }

        $storage = $this->replicator->getPrimary();
        $data = $storage->findById($scalarId);
        if (is_null($data)) {
            if ($notFoundException) {
                throw $notFoundException;
            }
            return null;
        }
        return $this->populateOne($data);
    }

    /**
     * @param Id[]|array $ids
     * @return ModelInterface[]
     * @throws DuplicateModelException
     * @throws UnassignedIdException
     * @throws SerializerException
     */
    public function findByIds($ids): array
    {
        $scalarIds = IdHelper::scalarizeMany(...$ids);
        $registered = $this->getRegisteredByIds(...$scalarIds);

        if (count($ids) === count($registered)) {
            return $registered;
        }

        $storage = $this->replicator->getPrimary();
        $array = $storage->findByIds($scalarIds);
        return $this->populateMany($array);
    }

    /**
     * @param ModelInterface[] $models
     * @return SplObjectStorage
     * @throws DuplicateModelException
     * @throws UnassignedIdException
     * @throws SerializerException
     */
    public function refresh(ModelInterface ...$models): SplObjectStorage
    {
        $map = new SplObjectStorage();
        $models = ModelHelper::indexById(...$models);
        $storage = $this->replicator->getPrimary();
        $storageDataArray = $storage->findByIds(array_keys($models));
        foreach ($storageDataArray as $id => $storageData) {
            $model = $models[$id];
            unset($models[$id]);
            $refreshed = $this->getMapper()->deserialize($storageData);
            $map[$model] = $refreshed;
            $this->register($refreshed, true);
        }

        foreach ($models as $model) {
            $map[$model] = null;
        }

        return $map;
    }

    /**
     * @param Transaction $transaction
     * @throws DuplicateModelException
     * @throws UnassignedIdException
     * @throws SerializerException
     */
    public function commit(Transaction $transaction): void
    {
        foreach ($this->classes() as $class) {
            foreach ($transaction->getPersisted($class) as $model) {
                $data = $this->getMapper()->serialize($model);
                if ($this->isRegistered($model, true)) {
                    $this->replicator->update($data, $transaction);
                } else {
                    $this->replicator->insert($data, $transaction);
                    $this->register($model);
                }
            }

            foreach ($transaction->getDeleted($class) as $model) {
                $this->replicator->delete((string) $model->id(), $transaction);
                $this->unregister($model);
            }
        }
    }

    /**
     * Free-up model from memory. Warning! After calling this method, existed models in commit may be attempting insert
     * instead of update
     */
    public function freeUpMemory(): void
    {
        $this->registered = [];
        $this->replicator->freeUpMemory();
    }

    abstract protected function getMapper(): MapperInterface;

    abstract public function classes(): array;

    /**
     * @param StorageData $data
     * @return ModelInterface
     * @throws DuplicateModelException
     * @throws UnassignedIdException
     * @throws SerializerException
     */
    protected function populateOne(StorageData $data): ModelInterface
    {
        $model = $this->getMapper()->deserialize($data);

        if ($this->isRegistered($model)) {
            return $this->registered[get_class($model)][(string) $model->id()];
        }

        $this->register($model);
        return $model;
    }


    /**
     * @param StorageData[] $array
     * @return ModelInterface[]
     * @throws DuplicateModelException
     * @throws UnassignedIdException
     * @throws SerializerException
     */
    protected function populateMany(array $array): array
    {
        $models = [];
        foreach ($array as $key => $data) {
            $models[$key] = $this->populateOne($data);
        }
        return $models;
    }

    /**
     * @param ModelInterface $model
     * @param bool $strict
     * @return bool
     */
    protected function isRegistered(ModelInterface $model, bool $strict = false): bool
    {
        $registered = $this->registered[get_class($model)][(string) $model->id()] ?? null;
        return $strict ? $registered === $model : !is_null($registered);
    }

    /**
     * @param string ...$ids
     * @return ModelInterface[]
     */
    protected function getRegisteredByIds(string ...$ids): array
    {
        $result = [];
        foreach ($this->registered as $models) {
            foreach ($models as $id => $model) {
                if (in_array($id, $ids)) {
                    $result[$id] = $model;
                }
            }
        }
        return $result;
    }

    /**
     * @param ModelInterface $model
     * @param bool $refresh
     * @throws DuplicateModelException
     * @throws UnassignedIdException
     */
    protected function register(ModelInterface $model, bool $refresh = false): void
    {
        if (!$model->id()->isAssigned()) {
            throw new UnassignedIdException('Model without permanent id can not be registered');
        }

        $class = get_class($model);
        $id = (string) $model->id();

        if (isset($this->registered[$class][$id]) && $this->registered[$class][$id] !== $model && $refresh === false) {
            throw new DuplicateModelException("Model with class '{$class}' and id '{$id}' already registered");
        }

        Reference::to($model);

        $this->registered[$class][$id] = $model;
    }

    protected function unregister(ModelInterface $model): void
    {
        if ($model->id()->isAssigned()) {
            unset($this->registered[get_class($model)][(string) $model->id()]);
        }
    }

}