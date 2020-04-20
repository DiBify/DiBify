<?php

namespace DiBify\DiBify\Mock;


use DiBify\DiBify\Manager\Commit;
use DiBify\DiBify\Mappers\MapperInterface;
use DiBify\DiBify\Model\ModelInterface;
use DiBify\DiBify\Replicator\DirectReplicator;
use Exception;

abstract class Repository extends \DiBify\DiBify\Repository\Repository
{

    /** @var ModelInterface */
    private $models;

    public function __construct()
    {
        parent::__construct(new DirectReplicator([]));
        $class = $this->getClassName();
        $this->models = [
            '1' => new $class(1),
            '2' => new $class(2),
            '3' => new $class(3),
        ];
    }

    /**
     * @inheritDoc
     */
    public function findById($id, Exception $notFoundException = null): ?ModelInterface
    {
        $model = $this->models[(string) $id] ?? null;
        if (is_null($model) && $notFoundException) {
            throw $notFoundException;
        }

        if ($model) {
            $this->register($model);
        }

        return $model;
    }

    /**
     * @inheritDoc
     */
    public function findByIds($ids): array
    {
        $models = [];
        foreach ($ids as $id) {
            if ($model = $this->findById($id)) {
                $this->register($model);
                $models[] = $model;
            }
        }
        return $models;
    }

    /**
     * @inheritDoc
     */
    public function commit(Commit $commit): void
    {
        $persistedModels = $commit->getPersisted($this->getClassName());
        foreach ($persistedModels as $model) {
            $this->modelException($model);
            $this->models[(string) $model->id()] = $model;
            $this->register($model);
        }

        $deletedModels = $commit->getDeleted($this->getClassName());
        foreach ($deletedModels as $model) {
            $this->modelException($model);
            unset($this->models[(string) $model->id()]);
            $this->unregister($model);
        }
    }

    public function getRegistered(): array
    {
        return $this->registered;
    }

    /**
     * @param ModelInterface $model
     * @throws Exception
     */
    protected function modelException(ModelInterface $model)
    {
        if ($model->id()->isEqual('exception')) {
            throw new Exception('Some repository exception');
        }
    }

    protected function getMapper(): MapperInterface
    {
        //Not needed for tests
    }

    abstract protected function getClassName(): string;

}