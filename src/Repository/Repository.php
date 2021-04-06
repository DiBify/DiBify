<?php
/**
 * @author Timur Kasumov (aka XAKEPEHOK)
 * Datetime: 15.03.2017 15:10
 */

namespace DiBify\DiBify\Repository;

use DiBify\DiBify\Exceptions\DuplicateModelException;
use DiBify\DiBify\Exceptions\NotPermanentIdException;
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

    /** @var ModelInterface[] */
    protected $registered;

    /** @var ReplicatorInterface */
    protected $replicator;

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
        $storage = $this->replicator->getPrimary();
        $data = $storage->findById((string) $id);
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
     * @throws NotPermanentIdException
     * @throws SerializerException
     */
    public function findByIds($ids): array
    {
        $storage = $this->replicator->getPrimary();
        $array = $storage->findByIds(IdHelper::scalarizeMany($ids));
        return $this->populateMany($array);
    }

    /**
     * @param ModelInterface[] $models
     * @return SplObjectStorage
     * @throws DuplicateModelException
     * @throws NotPermanentIdException
     * @throws SerializerException
     */
    public function refresh($models): SplObjectStorage
    {
        $map = new SplObjectStorage();
        $models = ModelHelper::indexById($models);
        $storage = $this->replicator->getPrimary();
        $storageDataArray = $storage->findByIds(array_keys($models));
        foreach ($storageDataArray as $id => $storageData) {
            $model = $models[$id];
            unset($models[$id]);
            $refreshed = $this->getMapper()->deserialize($storageData);

            $modelHash = md5(var_export($model, true));
            $refreshedHash = md5(var_export($refreshed, true));

            if ($modelHash === $refreshedHash) {
                $map[$model] = $model;
            } else {
                $map[$model] = $refreshed;
                $this->register($model, true);
            }
        }

        foreach ($models as $id => $model) {
            $map[$model] = null;
        }

        return $map;
    }

    /**
     * @param Transaction $transaction
     * @throws DuplicateModelException
     * @throws NotPermanentIdException
     * @throws SerializerException
     */
    public function commit(Transaction $transaction): void
    {
        foreach ($this->classes() as $class) {
            foreach ($transaction->getPersisted($class) as $model) {
                $data = $this->getMapper()->serialize($model);
                if ($this->isRegistered($model)) {
                    $this->replicator->update($data);
                } else {
                    $this->replicator->insert($data);
                    $this->register($model);
                }
            }

            foreach ($transaction->getDeleted($class) as $model) {
                $this->replicator->delete((string) $model->id());
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
    }

    abstract protected function getMapper(): MapperInterface;

    abstract public function classes(): array;

    /**
     * @param StorageData $data
     * @return ModelInterface
     * @throws DuplicateModelException
     * @throws NotPermanentIdException
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
     * @throws NotPermanentIdException
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
     * @return bool
     */
    protected function isRegistered(ModelInterface $model): bool
    {
        return isset($this->registered[get_class($model)][(string) $model->id()]);
    }

    /**
     * @param ModelInterface $model
     * @param bool $refresh
     * @throws DuplicateModelException
     * @throws NotPermanentIdException
     */
    protected function register(ModelInterface $model, $refresh = false): void
    {
        if (!$model->id()->isAssigned()) {
            throw new NotPermanentIdException('Model without permanent id can not be registered');
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