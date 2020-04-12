<?php
/**
 * @author Timur Kasumov (aka XAKEPEHOK)
 * Datetime: 15.03.2017 15:10
 */

namespace DiBify\DiBify\Repository;

use DiBify\DiBify\Exceptions\DuplicateModelException;
use DiBify\DiBify\Exceptions\NotPermanentIdException;
use DiBify\DiBify\Helpers\IdHelper;
use DiBify\DiBify\Id\Id;
use DiBify\DiBify\Manager\Commit;
use DiBify\DiBify\Model\Link;
use DiBify\DiBify\Model\ModelInterface;
use DiBify\DiBify\Replicator\ReplicatorInterface;
use DiBify\DiBify\Repository\Storage\StorageData;
use Exception;

abstract class Repository
{

    /** @var ModelInterface[] */
    protected $registered;

    /** @var ReplicatorInterface */
    private $replicator;

    public function __construct(ReplicatorInterface $replicator)
    {
        $this->replicator = $replicator;
    }

    /**
     * @param Id|int|string $id
     * @param Exception|null $notFoundException
     * @return ModelInterface|null
     * @throws Exception
     */
    public function findById($id, Exception $notFoundException = null): ?ModelInterface
    {
        $storage = $this->replicator->getStorage();
        $data = $storage->findById((string) $id);
        if (is_null($data)) {
            throw $notFoundException;
        }
        return $this->populateOne($data);
    }

    /**
     * @param Id[]|array $ids
     * @return ModelInterface[]
     */
    public function findByIds($ids): array
    {
        $storage = $this->replicator->getStorage();
        $array = $storage->findByIds(IdHelper::scalarizeMany($ids));
        return $this->populateMany($array);
    }

    /**
     * @param Commit $commit
     */
    abstract public function commit(Commit $commit): void;

    /**
     * Освобождает из памяти загруженные модели.
     * ВНИМАНИЕ: после освобождения памяти в случае сохранения существующей модели через self::save()
     * в БД будет вставлена новая запись вместо обновления существующей
     */
    public function freeUpMemory(): void
    {
        $this->registered = [];
    }

    /**
     * @param ModelInterface $model
     * @return StorageData
     */
    abstract protected function extract(ModelInterface $model): StorageData;

    /**
     * @param StorageData $data
     * @return ModelInterface
     */
    abstract protected function hydrate(StorageData $data): ModelInterface;

    /**
     * @param StorageData $data
     * @return ModelInterface
     * @throws DuplicateModelException
     * @throws NotPermanentIdException
     */
    protected function populateOne(StorageData $data): ModelInterface
    {
        $model = $this->hydrate($data);

        if ($registered = $this->registered[(string) $model->id()] ?? null) {
            return $registered;
        }

        $this->register($model);
        return $model;
    }


    /**
     * @param StorageData[] $array
     * @return ModelInterface[]
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
     * @throws DuplicateModelException
     * @throws NotPermanentIdException
     */
    protected function register(ModelInterface $model): void
    {
        if (!$model->id()->isAssigned()) {
            throw new NotPermanentIdException('Model without permanent id can not be registered');
        }

        $class = get_class($model);
        $id = (string) $model->id();

        if (isset($this->registered[$class][$id]) && $this->registered[$class][$id] !== $model) {
            throw new DuplicateModelException("Model with class '{$class}' and id '{$id}' already registered");
        }

        Link::to($model);

        $this->registered[$class][$id] = $model;
    }

    protected function unregister(ModelInterface $model): void
    {
        if ($model->id()->isAssigned()) {
            unset($this->registered[get_class($model)][(string) $model->id()]);
        }
    }

}