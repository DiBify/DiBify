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

    protected ?RetryPolicy $retryPolicy;

    protected array $metadata = [];

    /** @var callable[][]  */
    protected array $eventHandlers = [];

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
    public function __construct(array $persisted = [], array $deleted = [], ?RetryPolicy $retryPolicy = null)
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
        $this->retryPolicy = $retryPolicy;
    }

    public function id(): Id
    {
        return $this->id;
    }

    /**
     * @return ModelInterface[]
     */
    public function getModels(): array
    {
        return [...array_values($this->persisted), ...array_values($this->deleted)];
    }

    /**
     * @param string ...$modelClasses
     * @return ModelInterface[]
     */
    public function getPersisted(string ...$modelClasses): array
    {
        return $this->filter($this->persisted, ...$modelClasses);
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
                self::split(self::$persistsWith, $model, $linkedModel);
                $this->persists($linkedModel);
            }

            unset(self::$persistsWith[$model]);

            foreach (self::$withPersistsDelete[$model] ?? [] as $linkedModel) {
                self::split(self::$withPersistsDelete, $model, $linkedModel);
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
     * @param string ...$modelClasses
     * @return ModelInterface[]
     */
    public function getDeleted(string ...$modelClasses): array
    {
        return $this->filter($this->deleted, ...$modelClasses);
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
                self::split(self::$deleteWith, $model, $linkedModel);
                $this->delete($linkedModel);
            }

            unset(self::$deleteWith[$model]);

            foreach (self::$withDeletePersists[$model] ?? [] as $linkedModel) {
                self::split(self::$withDeletePersists, $model, $linkedModel);
                $this->persists($linkedModel);
            }

            unset(self::$withDeletePersists[$model]);
        }
    }

    public function resetDeleted(): void
    {
        $this->deleted = [];
    }

    public function getRetryPolicy(): ?RetryPolicy
    {
        return $this->retryPolicy;
    }

    public function getMetadata(string $key): mixed
    {
        return $this->metadata[$key] ?? null;
    }

    public function setMetadata(string $key, $value): mixed
    {
        return $this->metadata[$key] = $value;
    }

    public function addEventHandler(TransactionEvent $event, callable $handler): void
    {
        $this->eventHandlers[$event->value][] = $handler;
    }

    public function triggerEvent(TransactionEvent $event): void
    {
        $handlers = $this->eventHandlers[$event->value] ?? [];
        foreach ($handlers as $handler) {
            $handler();
        }
        $this->eventHandlers[$event->value] = [];
    }

    /**
     * @param array $models
     * @param string ...$modelClasses
     * @return array
     */
    private function filter(array $models, string ...$modelClasses): array
    {
        if (count($modelClasses) === 0) {
            return array_values($models);
        }

        return array_values(
            array_filter($models, function (ModelInterface $model) use ($modelClasses) {
            return in_array(get_class($model), $modelClasses);
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

    private static function split(SplObjectStorage $map, ModelInterface $primary, ModelInterface $model): void
    {
        $current = $map[$primary] ?? [];
        $map[$primary] = array_filter($current, function (ModelInterface $inArrayModel) use ($model) {
            return $inArrayModel !== $model;
        });
    }

    public static function freeUpMemory(): void
    {
        self::$persistsWith = new SplObjectStorage();
        self::$deleteWith = new SplObjectStorage();
        self::$withDeletePersists = new SplObjectStorage();
        self::$withPersistsDelete = new SplObjectStorage();
    }

}