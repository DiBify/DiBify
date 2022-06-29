<?php
/**
 * @author Timur Kasumov (aka XAKEPEHOK)
 * Datetime: 19.05.2020 14:19
 */
namespace DiBify\DiBify\Manager;


use DiBify\DiBify\Id\Id;
use DiBify\DiBify\Id\UuidGenerator;
use DiBify\DiBify\Model\ModelInterface;
use Exception;

class Transaction
{
    protected Id $id;

    /** @var ModelInterface[] */
    protected array $persisted = [];

    /** @var ModelInterface[] */
    protected array $deleted = [];

    /** @var ModelInterface[] */
    protected array $foreignKeyModels = [];

    protected array $metadata = [];

    /**
     * Commit constructor.
     * @param ModelInterface[] $persisted
     * @param ModelInterface[] $deleted
     * @throws Exception
     */
    public function __construct(array $persisted = [], array $deleted = [], array $foreignKeyModels = [])
    {
        $this->id = new Id(UuidGenerator::generate());
        $this->persists(...$persisted);
        $this->delete(...$deleted);
        $this->addForeignKeyModels(...$foreignKeyModels);
    }

    public function id(): Id
    {
        return $this->id;
    }

    /**
     * @param string|null $modelClass
     * @return ModelInterface[]
     */
    public function getPersisted(string $modelClass = null): array
    {
        return $this->filter($this->persisted, $modelClass);
    }

    public function getPersistedOne(string $modelClass = null): ?ModelInterface
    {
        $persisted = $this->getPersisted($modelClass);
        $first = array_key_first($persisted);
        if (is_null($first)) {
            return null;
        }
        return $persisted[$first];
    }

    /**
     * Prepare model to store it in DB
     * @param ModelInterface[] $models
     */
    public function persists(ModelInterface ...$models): void
    {
        foreach ($models as $model) {
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
     * @param string|null $modelClass
     * @return ModelInterface[]
     */
    public function getDeleted(string $modelClass = null): array
    {
        return $this->filter($this->deleted, $modelClass);
    }

    public function getDeletedOne(string $modelClass = null): ?ModelInterface
    {
        $persisted = $this->getDeleted($modelClass);
        $first = array_key_first($persisted);
        if (is_null($first)) {
            return null;
        }
        return $persisted[$first];
    }

    public function isDeleted(ModelInterface $model): bool
    {
        $hash = spl_object_hash($model);
        return isset($this->deleted[$hash]);
    }

    /**
     * Prepare models for delete it from DB
     * @param ModelInterface[] $models
     */
    public function delete(ModelInterface ...$models): void
    {
        foreach ($models as $model) {
            $hash = spl_object_hash($model);
            $this->deleted[$hash] = $model;
            unset($this->persisted[$hash]);
        }
    }

    public function resetDeleted(): void
    {
        $this->deleted = [];
    }

    /**
     * @return ModelInterface[]
     */
    public function getForeignKeyModels(string $modelClass = null): array
    {
        return $this->filter($this->foreignKeyModels, $modelClass);
    }

    public function addForeignKeyModels(ModelInterface ...$models): void
    {
        foreach ($models as $model) {
            $hash = spl_object_hash($model);
            $this->foreignKeyModels[$hash] = $model;
        }
    }

    public function removeForeignKeyModels(ModelInterface ...$models): void
    {
        foreach ($models as $model) {
            $hash = spl_object_hash($model);
            unset($this->foreignKeyModels[$hash]);
        }
    }

    public function resetForeignKeyModels(): void
    {
        $this->deleted = [];
    }

    public function getMetadata(string $key)
    {
        return $this->metadata[$key] ?? null;
    }

    public function setMetadata(string $key, $value)
    {
        return $this->metadata[$key] = $value;
    }

    /**
     * @param ModelInterface[] $models
     * @param string|null $modelClass
     * @return ModelInterface[]
     */
    private function filter(array $models, string $modelClass = null): array
    {
        if ($modelClass === null) {
            return array_values($models);
        }

        return array_values(
            array_filter($models, function (ModelInterface $model) use ($modelClass) {
            return get_class($model) === $modelClass;
        }));
    }

}