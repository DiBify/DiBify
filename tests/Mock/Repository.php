<?php

namespace DiBify\DiBify\Mock;


use DiBify\DiBify\Manager\Transaction;
use DiBify\DiBify\Mappers\IdMapper;
use DiBify\DiBify\Mappers\MapperInterface;
use DiBify\DiBify\Mappers\ModelMapper;
use DiBify\DiBify\Mappers\NullOrMapper;
use DiBify\DiBify\Mappers\StringMapper;
use DiBify\DiBify\Model\ModelInterface;
use Exception;

abstract class Repository extends \DiBify\DiBify\Repository\Repository
{

    /** @var ModelInterface */
    public $models;

    public function __construct()
    {
        parent::__construct(new TestReplicator());
        $class = current($this->classes());
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

    public function getRegistered(): array
    {
        return $this->registered;
    }

    public function commit(Transaction $transaction): void
    {
        parent::commit($transaction);
        foreach ($this->classes() as $class) {
            foreach ($transaction->getPersisted($class) as $model) {
                $this->modelException($model);
                $this->models[(string) $model->id()] = $model;
            }

            foreach ($transaction->getDeleted($class) as $model) {
                $this->modelException($model);
                unset($this->models[(string) $model->id()]);
            }
        }
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
        $class = current($this->classes());
        return new ModelMapper($class, [
            'id' => IdMapper::getInstance(),
            'otherId' => new NullOrMapper(IdMapper::getInstance()),
            'custom' => new NullOrMapper(StringMapper::getInstance()),
        ]);
    }

}