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
use SplObjectStorage;

class Transaction
{
    protected Id $id;

    /** @var ModelInterface[] */
    protected array $persisted = [];

    /** @var ModelInterface[] */
    protected array $deleted = [];

    protected array $metadata = [];

    private static SplObjectStorage $persistsWith;

    private static SplObjectStorage $deleteWith;

    private static SplObjectStorage $withDeletePersists;

    private static SplObjectStorage $withPersistsDelete;

    /**
     * Commit constructor.
     * @param ModelInterface[] $persisted
     * @param ModelInterface[] $deleted
     * @throws Exception
     */
    public function __construct(array $persisted = [], array $deleted = [])
    {
        if (!isset(self::$persistsWith)) {
            self::$persistsWith = new SplObjectStorage();
        }

        if (!isset(self::$deleteWith)) {
            self::$deleteWith = new SplObjectStorage();
        }

        if (!isset(self::$withDeletePersists)) {
            self::$withDeletePersists = new SplObjectStorage();
        }

        if (!isset(self::$withPersistsDelete)) {
            self::$withPersistsDelete = new SplObjectStorage();
        }

        $this->id = new Id(UuidGenerator::generate());
        $this->persists(...$persisted);
        $this->delete(...$deleted);
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

            foreach (self::$persistsWith[$model] ?? [] as $linkedModel) {
                $this->persists($linkedModel);
            }

            unset(self::$persistsWith[$model]);

            foreach (self::$withPersistsDelete[$model] ?? [] as $linkedModel) {
                $this->delete($linkedModel);
            }

            unset(self::$withPersistsDelete[$model]);
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

            foreach (self::$deleteWith[$model] ?? [] as $linkedModel) {
                $this->delete($linkedModel);
            }

            unset(self::$deleteWith[$model]);

            foreach (self::$withDeletePersists[$model] ?? [] as $linkedModel) {
                $this->persists($linkedModel);
            }

            unset(self::$withDeletePersists[$model]);
        }
    }

    public function resetDeleted(): void
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

    public static function persistsWith(ModelInterface $primary, ModelInterface ...$models): void
    {
        if (!isset(self::$persistsWith)) {
            self::$persistsWith = new SplObjectStorage();
        }
        self::together(self::$persistsWith, $primary, ...$models);
    }

    public static function isPersistsWith(ModelInterface $primary, ModelInterface $secondary): bool
    {
        $models = self::$persistsWith[$primary] ?? [];
        return in_array($secondary, $models, true);
    }

    public static function deleteWith(ModelInterface $primary, ModelInterface ...$models): void
    {
        if (!isset(self::$deleteWith)) {
            self::$deleteWith = new SplObjectStorage();
        }
        self::together(self::$deleteWith, $primary, ...$models);
    }

    public static function isDeleteWith(ModelInterface $primary, ModelInterface $secondary): bool
    {
        $models = self::$deleteWith[$primary] ?? [];
        return in_array($secondary, $models, true);
    }

    public static function withDeletePersists(ModelInterface $primary, ModelInterface ...$models): void
    {
        if (!isset(self::$withDeletePersists)) {
            self::$withDeletePersists = new SplObjectStorage();
        }
        self::together(self::$withDeletePersists, $primary, ...$models);
    }

    public static function isWithDeletePersists(ModelInterface $primary, ModelInterface $secondary): bool
    {
        $models = self::$withDeletePersists[$primary] ?? [];
        return in_array($secondary, $models, true);
    }

    public static function withPersistsDelete(ModelInterface $primary, ModelInterface ...$models): void
    {
        if (!isset(self::$withPersistsDelete)) {
            self::$withPersistsDelete = new SplObjectStorage();
        }
        self::together(self::$withPersistsDelete, $primary, ...$models);
    }

    public static function isWithPersistsDelete(ModelInterface $primary, ModelInterface $secondary): bool
    {
        $models = self::$withPersistsDelete[$primary] ?? [];
        return in_array($secondary, $models, true);
    }

    private static function together(SplObjectStorage $map, ModelInterface $primary, ModelInterface ...$models): void
    {
        $current = $map[$primary] ?? [];
        $map[$primary] = array_merge($current, $models);
    }

    public static function freeUpMemory(): void
    {
        self::$persistsWith = new SplObjectStorage();
        self::$deleteWith = new SplObjectStorage();
        self::$withDeletePersists = new SplObjectStorage();
        self::$withPersistsDelete = new SplObjectStorage();
    }

}